<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Libraries\Echarts;
use App\Question;
use App\Answer;
use App\Datapoint;
use App\Partnership;
use App\Sector;
use App\Option;
use Illuminate\Support\Facades\Cache;

class ChartController extends Controller
{
	public function __construct() {
        $this->echarts = new Echarts();
        $this->collections = collect();
        $this->rsrOverview = collect();
        $this->rsrResultFilter = collect(['UII-1', 'UII-2', 'UII-3', 'UII-4', 'UII-5', 'UII-6', 'UII-7', 'UII-8']);
        $this->genderTextLegends = collect(['sw' => 'Senior Women', 'jw' => 'Junior Women', 'sm' => 'Senior Men', 'jm' => 'Junior Men']);
    }

    public function workStream(Request $request, Question $questions)
    {
        $values = $questions->where('question_id',30100022)
                            ->with('answers.datapoints.country')
                            ->with('options')->first();
        $answers = collect($values->answers)->map(function($answer) {
            return array(
                'country' => $answer->datapoints->country->name,
                'values' => explode('|', $answer->text)
            );
        })->groupBy('country');
        if (count($answers) === 0) {
            return response('no data available', 503);
        };
        $answers = $answers->map(function($data, $key){
            $list = collect();
            $data->each(function($d) use ($list) {
                $list->push($d['values']);
            });
            return collect($list->flatten(0))->countBy();
        });
        $series = collect($values->options)->map(function($option) use ($answers) {
            $text = $option['text'];
            $data = $answers->map(function($data, $key) use ($answers, $text) {
                if (isset($answers[$key][$text])){
                    return $answers[$key][$text];
                };
                return null;
            });
            return ["name" => $text, "data" => $data->values(), "stack" => "category"];
        });
        $categories = $answers->keys();
        $legends = $series->pluck('name');
		return $this->echarts->generateBarCharts($legends, $categories, "Horizontal", $series);
    }

    public function organisationForms(Request $request, Datapoint $datapoints, Partnership $partnerships)
    {
        $countries = $partnerships->has('childrens')->get();
        $data = $datapoints->select('datapoint_id', 'form_id', 'country_id')
                       ->where('survey_group_id', 2)
                       ->with('forms')
                       ->with('country')
                       ->get()
                       ->transform(function($dt){
                           $dt->form_name = $dt->forms->name;
                           $dt->country_name = $dt->country->name;
                           return $dt->makeHidden(['forms','country']);
                       });
        if (count($data) === 0) {
            return response('no data available', 503);
        };
        $projects = $data->groupBy('form_name');
        $series = collect($projects)
            ->map(function($data, $key){
                return $data->countBy('country_name');
            })
            ->map(function($data, $key) use ($countries) {
                $count = collect();
                collect($countries)->each(function($country) use ($data, $key, $count) {
                    $country = $country['name'];
                    if (isset($data[$country])){
                        $count->push($data[$country]);
                        return;
                    }
                    $count->push(null);
                    return;
                });
                return ["name" => $key, "data" => $count, "stack" => "category"];
            })->values();
        $legends = $series->pluck("name");
        $categories = $countries->pluck("name");
		return $this->echarts->generateBarCharts($legends, $categories, "Horizontal", $series);
    }

    public function rnrGender(Request $request, Answer $answers) {
        $femaleold = ['36030007'];
        $femaleyoung = ['24030004'];
        $maleold = ['20030002'];
        $maleyoung = ['24030005'];
        $question_id = collect([$femaleold, $femaleyoung, $maleold, $maleyoung])->flatten(0);
        $all = $answers->whereIn('question_id', $question_id);
        $hasCountry = false;
        if (isset($request->country_id) || isset($request->start)) {
            $hasCountry = true;
            if ($request->country_id === "0") {
                $hasCountry = false;
            }
            $datapoints_id = $this->filterQuery($request);
            $all = $all->whereIn('datapoint_id',$datapoints_id);
        }
        if ($hasCountry) {
            $all = $all->with('datapoints.partnership')->get()->transform(function($dt){
                $dt->country = ($dt->datapoints->partnership === null) ? "NA" : $dt->datapoints->partnership->name;
                return $dt->makeHidden('datapoints');
            });
        }
        if (!$hasCountry) {
            $all = $all->with('datapoints.country')->get()->transform(function($dt){
                $dt->country = ($dt->datapoints->country->name === null) ? "NA" : $dt->datapoints->country->name;
                return $dt->makeHidden('datapoints');
            });
        }
        if (count($all) === 0) {
            return response('no data available', 503);
        };
        $legends = $this->genderTextLegends->values();
        $all = collect($all)->sortByDesc('country')->values()->groupBy('country')->map(function($countries)
            use ($femaleold, $femaleyoung, $maleold, $maleyoung)
        {
            $countries = $countries->map(function($country)
                use ($femaleold, $femaleyoung, $maleold, $maleyoung)
            {
                $country->total = (int) $country->value;
                if (collect($femaleold)->contains($country->question_id)){
                    $country->participant = $this->genderTextLegends['sw'];
                }
                if (collect($femaleyoung)->contains($country->question_id)){
                    $country->participant = $this->genderTextLegends['jw'];
                }
                if (collect($maleold)->contains($country->question_id)){
                    $country->participant = $this->genderTextLegends['sm'];
                }
                if (collect($maleyoung)->contains($country->question_id)){
                    $country->participant = $this->genderTextLegends['jm'];
                }
                return $country;
            });
            $countries = $countries->groupBy('participant');
            $countries = $countries->map(function($country) {
                return $country->sum('total');
            });
            return $countries;
        });
        $categories = $all->keys();
        $femaleold = collect();
        $femaleyoung = collect();
        $maleold = collect();
        $maleyoung = collect();
        $all = $all->map(function($data)
            use ($femaleold, $femaleyoung, $maleold, $maleyoung ){
                $data->map(function($dt, $key)
                use ($femaleold, $femaleyoung, $maleold, $maleyoung ){
                    if ($key === $this->genderTextLegends['sw']){
                        $femaleold->push($dt);
                    }
                    if ($key === $this->genderTextLegends['jw']){
                        $femaleyoung->push($dt);
                    }
                    if ($key === $this->genderTextLegends['sm']){
                        $maleold->push($dt);
                    }
                    if ($key === $this->genderTextLegends['jm']){
                        $maleyoung->push($dt);
                    }
                return $dt;
            });
            return $data;
        });
        $series = collect($legends)->map(function($legend)
            use ($femaleold, $femaleyoung, $maleold, $maleyoung ){
                $values = [];
                if ($legend === $this->genderTextLegends['sw']){
                    $values = $femaleold;
                }
                if ($legend === $this->genderTextLegends['jw']){
                    $values = $femaleyoung;
                }
                if ($legend === $this->genderTextLegends['sm']){
                    $values = $maleold;
                }
                if ($legend === $this->genderTextLegends['jm']){
                    $values = $maleyoung;
                }
            return array(
				"name" => $legend,
				"data" => $values,
				"stack" => "Gender",
            );
        });
		$type = "Horizontal";
		return $this->echarts->generateBarCharts($legends, $categories, $type, $series);
    }

    public function genderTotal(Request $request, Answer $answers) {
        $femaleold = ['36030007'];
        $femaleyoung = ['24030004'];
        $maleold = ['20030002'];
        $maleyoung = ['24030005'];
        $question_id = collect([$femaleold, $femaleyoung, $maleold, $maleyoung])->flatten(0);
        $all = $answers->whereIn('question_id', $question_id);
        if (isset($request->country_id) || isset($request->start)) {
            $datapoints_id = $this->filterQuery($request);
            $all = $all->whereIn('datapoint_id',$datapoints_id);
        }
        $all = $all->get();
        if (count($all) === 0) {
            return response('no data available', 503);
        };
        $legends = $this->genderTextLegends->values();
        $series = collect($all)->map(function($dt)
            use ($femaleold, $femaleyoung, $maleold, $maleyoung ){
                $dt->answer = (int) $dt->answer;
                if (collect($femaleold)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['sw'];
                }
                if (collect($femaleyoung)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['jw'];
                }
                if (collect($maleold)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['sm'];
                }
                if (collect($maleyoung)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['jm'];
                }
                return $dt;
            })->groupBy('participant')->map(function($part, $key){
                return array(
                    $key => $part->sum('text')
                );
            });
        $legends = $series->keys();
        $series = collect($series)->map(function($data, $key){
            return array(
                "name"=>$key,
                "value"=>$data[$key],
            );
        })->values();
		return $this->echarts->generateDonutCharts($legends, $series);
    }

    public function countryTotal(Request $request, Answer $answers) {
        $question_id = ['36030007', '24030004', '20030002', '24030005'];
        $answers = $answers->whereIn('question_id', $question_id);
        $hasCountry = false;
        if (isset($request->country_id) || isset($request->start)) {
            $hasCountry = true;
            if ($request->country_id === "0") {
                $hasCountry = false;
            }
            $datapoints_id = $this->filterQuery($request);
            $answers= $answers->whereIn('datapoint_id',$datapoints_id);
        }
        if ($hasCountry) {
            $answers = $answers->with('datapoints.partnership')->get()->transform(function($data){
                return [
                    'name' => Str::before($data->datapoints->partnership->name, '_'),
                    'value' => $data->value,
                ];
            });
        }
        if (!$hasCountry) {
            $answers = $answers->with('datapoints.country')->get()->transform(function($data){
                return [
                    'name' => $data->datapoints->country->name,
                    'value' => $data->value,
                ];
            });
        }
        if (count($answers) === 0) {
            return response('no data available', 503);
        };
        $answers = $answers->groupBy('name')->map(function($dt, $key) {
                return $dt->map(function($d){return $d['value'];})->sum();
        });
        $legends = $answers->keys();
        $series = collect($answers)->map(function($data, $key){
            return array(
                "name"=>$key,
                "value"=>$data,
            );
        })->values();
		return $this->echarts->generateDonutCharts($legends, $series);
    }

    private function filterPartnership($request, $results) {

        $filter_country = false;
        $filter_partner = false;
        $start = new Carbon('2018-01-01');
        $end = new Carbon(date("Y-m-d"));

        if (isset($request->country_id)) {
            $filter_country = true;
            if ($request->country_id === "0") {
                $filter_country = false;
            }
        }

        if (isset($request->partnership_id)) {
            $filter_partner = true;
            if ($request->partnership_id === "0") {
                $filter_partner = false;
            }
        }

        if (isset($request->start)){
            $start = new Carbon($request->start);
            $end = new Carbon($request->end);
        }

        $survey_codes = collect(config('surveys.forms'))->filter(function($survey){
            return $survey['name'] === "Organisation Forms";
        })->map(function($survey){
            return collect($survey['list'])->values()->map(function($list){
                return $list['form_id'];
            })->flatten(2);
        })->values()->flatten();

        if($filter_country){
            $results = $results->where('parent_id', $request->country_id);
        };
        if ($filter_partner) {
            $results = $results->where('id', $request->partnership_id);
        }
        $results = $results->with('partnership_datapoints')->with('parents')->get();
        if (count($results) === 0) {
            return response('no data available', 503);
        };

        $results = collect($results)->map(function($partners) use ($survey_codes, $start, $end) {
            $partnership_dp = collect($partners['partnership_datapoints'])->filter(function($dp) use ($survey_codes) {
                return collect($survey_codes)->contains($dp['form_id']);
            });
            $partnership_dp = $partnership_dp->map(function($dp){
                return ["date" => new Carbon(date($dp["submission_date"]))];
            })->values();
            $partnership_dp = $partnership_dp->whereBetween('date', [$start, $end]);
            $res['country'] = ($partners['parents'] === null) ? null : $partners['parents']['name'];
            $res['commodity'] = Str::before($partners['name'],'_');
            $res['project'] = Str::after($partners['name'],'_');
            $res['value'] = $partnership_dp->count();
            return $res;
        });
        $results = $results->reject(function($partners){
            return $partners['value'] === 0;
        })->values();
        return $results;
    }

    public function partnershipCharts(Request $request, Partnership $partnerships, Datapoint $datapoints) {
        $results = $this->filterPartnership($request, $partnerships);
        if (count($results) === 0) {
            return response('no data available', 503);
        };
        $results = $results->sortByDesc('value')->values();
        $categories = $results->groupBy('country')->keys();
        $series = $results->groupBy('project')->map(function($countries, $key) use ($results, $categories) {
            $data = collect();
            $categories->each(function($category) use ($countries, $data) {
                $countries->each(function($country) use ($category, $data) {
                    if ($category === $country['country']){
                        $data->push($country['value']);
                    } else {
                        $data->push(null);
                    }
                });
            });
            return [
                "name" => $key,
                "data" => $data,
                "stack" => "project"
            ];
        })->values();
        $type = "Horizontal";
        $legends = $series->map(function($legend){
            return $legend['name'];
        });
        return $this->echarts->generateBarCharts($legends, $categories, $type, $series);
    }

    public function partnershipTotalCharts(Request $request, Partnership $partnerships, Datapoint $datapoints) {
        $results = $this->filterPartnership($request, $partnerships);
        if (count($results) === 0) {
            return response('no data available', 503);
        };
        $series = $results->groupBy('country')->map(function($data, $key){
            $data = $data->map(function($val){
                return $val['value'];
            })->sum();
            return [
                "name" => $key,
                "value" => $data,
            ];
        })->values();
        $legends = $series->map(function($d){
            return $d['name'];
        });
		return $this->echarts->generateDonutCharts($legends, $series);
        return $this->echarts->generateBarCharts($legends, $categories, $type, $series);
    }

    public function partnershipCommodityCharts(Request $request, Partnership $partnerships, Datapoint $datapoints) {
        $results = $this->filterPartnership($request, $partnerships);
        if (count($results) === 0) {
            return response('no data available', 503);
        };
        $series = $results->groupBy('project')->map(function($data, $key){
            $data = $data->map(function($val){
                return $val['value'];
            })->sum();
            return [
                "name" => $key,
                "value" => $data,
            ];
        })->values();
        $legends = $series->map(function($d){
            return $d['name'];
        });
		$values = $series->map(function($d) {
            return $d['value'];
		});
		return $this->echarts->generateSimpleBarCharts($legends, $values);
    }

    public function genderCount(Request $request, Answer $answers)
    {
        $femaleold = ['36030007'];
        $femaleyoung = ['24030004'];
        $maleold = ['20030002'];
        $maleyoung = ['24030005'];
        $question_id = collect([$femaleold, $femaleyoung, $maleold, $maleyoung])->flatten(0);
        $all = $answers->whereIn('question_id', $question_id);
        if (isset($request->country_id)) {
            $datapoints_id = $this->filterQuery($request);
            $all = $all->whereIn('datapoint_id',$datapoints_id);
        }
        $all = collect($all->get())->map(function($dt, $key)
            use ($femaleold, $femaleyoung, $maleold, $maleyoung ){
                $dt->answer = (int) $dt->answer;
                if (collect($femaleold)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['sw'];
                }
                if (collect($femaleyoung)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['jw'];
                }
                if (collect($maleold)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['sm'];
                }
                if (collect($maleyoung)->contains($dt->question_id)){
                    $dt->participant = $this->genderTextLegends['jm'];
                }
                return $dt;
            })->groupBy('participant')->map(function($dt, $key){
                return [
                    "country" => $key,
                    "value" => $dt->sum('value'),
                ];
            })->values();
        return $all;
    }

    public function topThree(Request $request, Partnership $partnerships, Datapoint $datapoints)
    {
        $results = $partnerships;
        $showPartnership = false;
        $start = new Carbon('2018-01-01');
        $end = new Carbon(date("Y-m-d"));
        if (isset($request->country_id)) {
            if($request->country_id !== "0") {
                $showPartnership = true;
            };
        }
        if (!isset($request->country_id)) {
            $showPartnership = false;
        }
        if (isset($request->start)) {
            $start = new Carbon($request->start);
            $end = new Carbon($request->end);
        }
        if($showPartnership){
            $results = $results->where('id', $request->country_id)
                ->has('country_datapoints')
                ->with('country_datapoints.partnership')
                ->first();
            if (!$results) {
                return response('no data available', 503);

            }
            $results = $results->country_datapoints->transform(function($dt){
                return [
                    'country' => $dt->partnership->name,
                    'commodity' => Str::before($dt->partnership->name,'_'),
                    'project' => Str::after($dt->partnership->name,'_'),
                    'date' => new Carbon($dt->submission_date)
                ];
            })->groupBy('country');
            $results = collect($results)->map(function($dt, $key) use ($start, $end) {
                $filtered_date = collect($dt)->whereBetween('date', [$start, $end]);
                return [
                    'country' => $key,
                    'commodity' => Str::before($key, '_'),
                    'project' => Str::after($key, '_'),
                    'value' => $filtered_date->count(),
                ];
            })->values()->take(4);
            return $results;
        };
        if(!$showPartnership){
            $survey_codes = collect(config('surveys.forms'))->filter(function($survey){
                return $survey['name'] === "Organisation Forms";
            })->map(function($survey){
                return collect($survey['list'])->values()->map(function($list){
                    return $list['form_id'];
                })->flatten(2);
            })->values()->flatten();
            $results = $results
                ->has('partnership_datapoints')
                ->with('partnership_datapoints.partnership')
                ->with('parents')
                ->get();
            $results = collect($results)->map(function($partners) use ($survey_codes, $start, $end) {
                $partnership_dp = collect($partners['partnership_datapoints'])
                    ->filter(function($dp) use ($survey_codes) {
                    return collect($survey_codes)->contains($dp['form_id']);
                });
                $partnership_dp = $partnership_dp->map(function($dp){
                    return ["date" => new Carbon(date($dp["submission_date"]))];
                })->values();
                $partnership_dp = $partnership_dp->whereBetween('date', [$start, $end]);
                $res['country'] = ($partners['parents'] === null) ? null : $partners['parents']['name'];
                $res['commodity'] = Str::before($partners['name'],'_');
                $res['project'] = Str::after($partners['name'],'_');
                $res['value'] = count($partnership_dp);
                return $res;
            });
            $results = $results->reject(function($partners){
                return $partners['value'] === 0;
            })->values();
            $results = $results->sortByDesc('value')->values();
            $partners = $results->take(3);
            $total = [
                'country'=> $results->groupBy('country')->count()." Countries",
                'commodity' => $results->groupBy('commodity')->count(). " Partnerships",
                'project' => $results->groupBy('project')->count(). " Projects",
                'value' => $results->countBy('value')->flatten()->sum()
            ];
            $partners->push($total);
            return $partners;
        }
        $results = collect($results)->push(array(
            'country' => $partnerships->has('childrens')->count(),
            'commodity' => $partnerships->has('parents')->count(),
            'project' => 'Total Projects',
            'value' => $datapoints->count(),
        ));
        return $results;
    }

    public function mapCharts(Request $request, Partnership $partnerships, Datapoint $datapoints)
    {
        $data = $partnerships
            ->get()
            ->transform(function($dt){
                return array (
                    'name' => $dt->name,
                    'value' => ""
                );
            });
        $min = collect($data)->min('value');
        $max = collect($data)->max('value');
        return $this->echarts->generateMapCharts($data, $min, $max);
    }

	public function hierarchy(Request $request, Answer $answers)
	{
        //$organisation_id = [20140003, 28150003, 38120005, 38140006];
        $organisation_id = [14180001, 28150003, 38120005, 38140006];
        $organisation = $answers->whereIn('question_id',$organisation_id)
                                ->with('questions.form')
                                ->has('datapoints.partnership.parents')
                                ->with('datapoints.partnership.parents')->get()
                                ->transform(function($dt){
                                    return array(
                                        'country' => $dt->datapoints->partnership->parents->name,
                                        'partnership' => $dt->datapoints->partnership->name,
                                        'organisation' => $dt->text,
                                        'projects' => $dt->questions->form->name
                                    );
                                });
        $organisation = collect($organisation)->groupBy('country')->map(function($data, $country){
            $partnership = $data->groupBy("partnership")->map(function($data, $partnership) {
                $projects = $data->groupBy("projects")->map(function($data, $project){
                    $result = array(
                        "name" => $project,
                        "value" => "projects",
                        "children" => $data->map(function($data){
                            return array(
                                "name" => str_replace('_',' ', $data["organisation"]),
                                "value" => "organisation",
                                "itemStyle" => array("color" => "#609ba7"),
                                "label" => array("fontSize" => 12),
                            );
                        }),
                        "itemStyle" => array("color" => "#a43332"),
                        "label" => array("fontSize" => 12),
                    );
                    if (count($data)) {
                        $result["tooltip"] = array("formatter" => "Click to expand/collapse");
                    }
                    return $result;
                })->values();
                $result = array(
                    "name" => $partnership,
                    "value" => "partnership",
                    "children" => $projects,
                    "itemStyle" => array("color" => "#3b3b3b"),
                    "label" => array("fontSize" => 12),
                );
                if (count($projects)) {
                    $result["tooltip"] = array("formatter" => "Click to expand/collapse");
                }
                return $result;
            })->values();
            $result = array(
                "name" => $country,
                "value" => "countries",
                "children" => $partnership,
                "itemStyle" => array("color" => "#609ba7"),
                "label" => array("fontSize" =>  12),
            );
            if (count($partnership)) {
                $result["tooltip"] = array("formatter" => "Click to expand/collapse");
            }
            return $result;
        })->values();
        $result = array(
            "name" => "2SCALE",
            "value" => "Global",
            "children" => $organisation,
            "itemStyle" => array("color" => "#3b3b3b"),
            "label" => array("fontSize" => 14),
        );
        if (count($organisation)) {
            $result["tooltip"] = array("formatter" => "Click to expand/collapse");
        }
        return $result;
	}

    private function filterQuery($request) {
        $country_id = false;
        $partnerships_id = false;
        $datapoints = Datapoint::select('id');
        if(isset($request->country_id) && $request->country_id !== "0"){
            $country_id = $request->country_id;
            $datapoints = $datapoints->where('country_id',$country_id);
        }
        if(isset($request->partnership_id) && $request->partnership_id !== "0"){
            $partnerships_id = $request->partnership_id;
            $datapoints = $datapoints->where('partnership_id',$partnerships_id);
        }
        if(isset($request->start)){
            $start = date($request->start);
            $end = date($request->end);
            $datapoints = $datapoints->whereBetween('submission_date',[$start, $end]);
        };
        return $datapoints->get()->pluck('id');
    }

    public function getRsrDatatable(Request $request)
    {
        $partnershipId = null;
        if (isset($request->country_id) && $request->country_id !== "0") {
            $partnershipId = $request->country_id;
        }
        if (isset($request->partnership_id) && $request->partnership_id !== "0") {
            $partnershipId = $request->country_id; // generate datatables just from country level
            // $partnershipId = $request->partnership_id; // generate datatables from partnership level
        }
        // send withContribution = true to add 2scale/private contribution value
        return $this->getAndTransformRsrData($partnershipId, false, false, true);
    }

    /**
     * Function to rename UII8 - dimensions name and
     * total value indicator name
     */
    private function transfromUII8dimensionName($name, $isUII8)
    {
        // change UII 8 dimensions name
        $dimName = strtolower($name);
        if ($isUII8 && str_contains($dimName, 'total value')) {
            $dimName = "Total value(Euros) of financial services accessed by SHFs, MSMEs & SMEs";
        }
        if ($isUII8 && str_contains($dimName, 'shfs')) {
            $dimName = "#smallholder farmers";
        }
        if ($isUII8 &&  str_contains($dimName, 'micro-entrepreneurs')) {
            $dimName = "#MSMEs";
        }
        if ($isUII8 &&  str_contains($dimName, 'smes')) {
            $dimName = "#SMEs";
        }
        // eol change UII 8 dimensions name
        return $dimName;
    }

    /**
     * Function to add prefix to contribution title/name
     * for ordering purposes
     */
    private function addPrefixToContributionNameForOrdering($name)
    {
        if (str_contains($name, "Private sector contribution")) {
            // add 1 to put private contribution before 2scale contrib
            return "Z##1PSC (Euros)";
        }
        if (str_contains($name, "2SCALE's Contribution")) {
            return "Z##2SCALE contributions (Euros)";
        }
        return $name;
    }

    public function getAndTransformRsrData($partnershipId, $period_start=false, $period_end=false, $withContribution=false)
    {
        $pId = $partnershipId ? $partnershipId : 'all';
        $year = $period_start ? $period_start : "-0";
        $selector = $period_end ? $period_end : "-0";
        $cacheName = 'rsr-reports-'.$pId.$year.$selector;

        /** Get Cache */
        $rsrReportCache = Cache::get($cacheName);
        if ($rsrReportCache) {
            return $rsrReportCache;
        }

        $this->rsrOverview = \App\ViewRsrOverview::where('agg_type', 'max')->get();
        $rsrFilter = $this->rsrResultFilter;
        // ! Add contribution to show on uii table
        if ($withContribution) {
            $rsrFilter->push("Private sector contribution");
            $rsrFilter->push("2SCALE's Contribution");
        }
        $this->rsrResultFilter = $rsrFilter->toArray();

        $data = \App\RsrProject::where('partnership_id', $partnershipId)
                ->with(['rsr_results' => function ($query) use ($period_start, $period_end) {
                    // $query->orderBy('order');
                    $query->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                    $query->with(['rsr_indicators.rsr_periods' => function ($query) use ($period_start, $period_end) {
                        if ($period_start) {
                            $query->where('period_start', '>=', date($period_start));
                        }
                        if ($period_end) {
                            $query->where('period_end', '<=', date($period_end));
                        }
                        $query->with('rsr_period_dimension_values');
                    }]);
                    $query->with(['childrens' => function ($query) use ($period_start, $period_end) {
                        // $query->orderBy('order');
                        $query->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                        $query->with(['rsr_indicators.rsr_periods' => function ($query) use ($period_start, $period_end) {
                            if ($period_start) {
                                $query->where('period_start', '>=', date($period_start));
                            }
                            if ($period_end) {
                                $query->where('period_end', '<=', date($period_end));
                            }
                            $query->with('rsr_period_dimension_values');
                        }]);
                        $query->with(['childrens' => function ($query) use ($period_start, $period_end) {
                            // $query->orderBy('order');
                            $query->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                            $query->with(['rsr_indicators.rsr_periods' => function ($query) use ($period_start, $period_end) {
                                if ($period_start) {
                                    $query->where('period_start', '>=', date($period_start));
                                }
                                if ($period_end) {
                                    $query->where('period_end', '<=', date($period_end));
                                }
                                $query->with('rsr_period_dimension_values');
                            }]);
                        }]);
                    }]);
                }])->first();

        if (!$data) {
            return [];
        };

        $this->collections = collect();
        $data = $data['rsr_results']->transform(function ($res) {
            $uii = Str::before($res['title'], ': ');
            $title = '# of '.Str::after($res['title'], ': ');
            $isUII8 = str_contains($uii, 'UII-8');

            $hasChildrens = count($res['childrens']) > 0;
            $res['parent_project'] = null;
            $res['level'] = 1;
            $res = $this->aggregateRsrValues($res, $hasChildrens);
            $res = $this->aggregateRsrChildrenValues($res, 2);
            $res = Arr::except($res, ['childrens']);
            $res['columns'] = [
                'id' => $res['id'],
                'uii' => $uii,
                'title' => $title,
                'subtitle' => [],
            ];
            if ($res['rsr_indicators_count'] > 1 && count($res['rsr_dimensions']) === 0) {
                $subtitles = collect();
                $res['rsr_indicators']->each(function ($ind) use ($subtitles) {
                    $subtitles->push([
                        "name" => $ind['title'],
                        "values" => [],
                    ]);
                });
                $res['columns']= [
                    'id' => $res['id'],
                    'uii' => $uii,
                    'title' => $title,
                    'subtitle' => $subtitles,
                ];
            }
            if (count($res['rsr_dimensions']) > 0 && $res['rsr_indicators_count'] === 1) {
                # UII 8 : As in RSR
                $subtitles = collect();
                $res['rsr_dimensions']->each(function ($dim) use ($subtitles) {
                    $subtitles->push([
                        "name" => $dim['name'],
                        "values" => $dim['rsr_dimension_values']->pluck('name'),
                    ]);
                });
                $res['columns'] = [
                    'id' => $res['id'],
                    'uii' => $uii,
                    'title' => $title,
                    'subtitle' => $subtitles,
                ];
                # EOL UII 8 : As in RSR
            }
            if (count($res['rsr_dimensions']) > 0 && $res['rsr_indicators_count'] > 1) {
                # UII 8 : Male-led - Female-led
                // $res['columns'] = $res['rsr_dimensions']->map(function ($dim) use ($res) {
                //     $resultIds = collect(config('akvo-rsr.datatables.uii8_results_ids'));
                //     if ($resultIds->contains($res['id']) || $resultIds->contains($res['parent_result'])) {
                //         $dimensionIds = collect(config('akvo-rsr.datatables.ui8_dimension_ids'));
                //         if ($dimensionIds->contains($dim['id']) || $dimensionIds->contains($dim['parent_dimension_name'])) {
                //             return [
                //                 'id' => $res['id'],
                //                 'title' => $title,
                //                 'dimension' => $dim['name'],
                //                 'subtitle' => $dim['rsr_dimension_values']->pluck('name'),
                //             ];
                //         }
                //         return;
                //     }
                //     return [
                //         'id' => $res['id'],
                //         'title' => $title,
                //         'dimension' => $dim['name'],
                //         'subtitle' => $dim['rsr_dimension_values']->pluck('name'),
                //     ];
                // })->reject(function ($dim) {
                //     return $dim === null;
                // })->values()[0];
                # EOL UII 8 : Male-led - Female-led

                # UII 8 : As in RSR
                $subtitles = collect();
                $res['rsr_dimensions']->each(function ($dim) use ($subtitles, $isUII8) {
                    // change UII 8 dimensions name
                    $dimName = $this->transfromUII8dimensionName($dim['name'], $isUII8);
                    $subtitles->push([
                        "name" => $dimName,
                        "values" => $dim['rsr_dimension_values']->pluck('name'),
                    ]);
                });
                $res['rsr_indicators']->each(function ($ind) use ($subtitles, $isUII8) {
                    // change UII 8 ind title
                    $indTitle = $this->transfromUII8dimensionName($ind['title'], $isUII8);
                    $subtitles->push([
                        "name" => $indTitle,
                        "values" => [],
                    ]);
                });
                $res['columns'] = [
                    'id' => $res['id'],
                    'uii' => $uii,
                    'title' => $title,
                    'subtitle' => $subtitles,
                ];
                # EOL UII 8 : As in RSR
            }
            $res['uii'] = $uii;
            $this->collections->push($res);
            return $res;
        });

        // $parents = $this->collections->where('level', 1)->values();
        // ! filter not to show contribution value
        $parents = $this->collections->where('level', 1)->values()->reject(function ($item) {
            return !Str::contains($item['title'], $this->rsrResultFilter);
        })->values();
        $results = $parents->first()->only('rsr_project_id', 'project');
        $results['columns'] = $parents->map(function ($item) {
                                    $item['uii'] = $this->addPrefixToContributionNameForOrdering($item['uii']);
                                    return $item;
                                })->sortBy('uii')->values();

        $childs = $this->collections->where('parent_project', $results['rsr_project_id']);
        $results['childrens'] = $childs->unique('project')->values();
        if (count($results['childrens']) > 0) {
            $results['childrens'] = $results['childrens']->transform(function ($child) use ($childs) {
                $child = $child->only('rsr_project_id', 'project');
                // $child['columns'] = $childs->where('rsr_project_id', $child['rsr_project_id'])->values();
                // ! filter not to show contribution value
                $child['columns'] = $childs->where('rsr_project_id', $child['rsr_project_id'])->values()->reject(function ($item) {
                    return !Str::contains($item['title'], $this->rsrResultFilter);
                })->values()->map(function ($col) {
                    $col['uii'] = Str::before($col['title'], ': ');
                    $col['uii'] = $this->addPrefixToContributionNameForOrdering($col['uii']);
                    return $col;
                })->sortBy('uii')->values();

                $childs = $this->collections->where('parent_project', $child['rsr_project_id']);
                $child['childrens'] = $childs->unique('project')->values();
                if (count($child['childrens']) > 0) {
                    $child['childrens'] = $child['childrens']->transform(function ($child) use ($childs) {
                        $child = $child->only('rsr_project_id', 'project');
                        // $child['columns'] = $childs->where('rsr_project_id', $child['rsr_project_id'])->values();
                        // ! filter not to show contribution value
                        $child['columns'] = $childs->where('rsr_project_id', $child['rsr_project_id'])->values()->reject(function ($item) {
                            return !Str::contains($item['title'], $this->rsrResultFilter);
                        })->values()->map(function ($col) {
                            $col['uii'] = Str::before($col['title'], ': ');
                            $col['uii'] = $this->addPrefixToContributionNameForOrdering($col['uii']);
                            return $col;
                        })->sortBy('uii')->values();

                        return $child;
                    });
                }

                return $child;
            });
        }

        $rsrReport = [
            "config" => [
                "result_ids" => config('akvo-rsr.datatables.uii8_results_ids'),
                "url" => config('akvo-rsr.endpoints.rsr_page'),
            ],
            // ! filter not to show contribution value
            "columns" => $data->pluck('columns')->reject(function ($item) {
                            return !Str::contains($item['uii'], $this->rsrResultFilter);
                        })->values()->map(function ($item) {
                            $item['uii'] = $this->addPrefixToContributionNameForOrdering($item['uii']);
                            return $item;
                        })->sortBy('uii')->values(),
            "data" => $results,
        ];

        /** Put Cache */
        Cache::put($cacheName, $rsrReport, 86400);

        $this->rsrOverview = collect();
        return $rsrReport;
    }

    private function aggregateRsrChildrenValues($res, $level)
    {
        if (count($res['childrens']) === 0) {
            return $res;
        }
        $collections = collect();
        $res['childrens'] = $res['childrens']->transform(function ($child) use ($collections, $res, $level) {
            $hasChildrens = count($child['childrens']) > 0;
            $child['parent_project'] = $res['rsr_project_id'];
            $child['level'] = $level;
            $child = $this->aggregateRsrValues($child, $hasChildrens);
            // collect all childs dimensions value
            if (count($child['rsr_dimensions']) > 0) {
                foreach ($child['rsr_dimensions'] as $dim) {
                    $collections->push($dim['rsr_dimension_values']);
                }
            }
            $level += 1;
            $child = $this->aggregateRsrChildrenValues($child, $level);
            $child = Arr::except($child, ['childrens']);
            $this->collections->push($child);
            return $child;
        });
        // aggregate all value from children ( if the parent value 0, take it from children aggregate )
        //
        if ($res['total_target_value'] == 0) {
            $res['total_target_value'] = $res['childrens']->sum('total_target_value');
        }
        if ($res['total_actual_value'] == 0) {
            $res['total_actual_value'] = $res['childrens']->sum('total_actual_value');
        }
        if (count($res['rsr_dimensions']) > 0 && count($res['childrens']) !== 0) {
            // aggregate dimension value
            $res['rsr_dimensions'] = $res['rsr_dimensions']->transform(function ($dim)
                use ($collections) {
                $dim['rsr_dimension_values'] = $dim['rsr_dimension_values']->transform(function ($dimVal)
                    use ($collections) {
                    $values = $collections->flatten(1)->where('parent_dimension_value', $dimVal['rsr_dimension_value_id']);
                    if ($dimVal['value'] == 0) {
                        $dimVal['value'] = $values->sum('value');
                    }
                    if ($dimVal['total_actual_value'] == 0) {
                        $dimVal['total_actual_value'] = $values->sum('total_actual_value');
                    }
                    return $dimVal;
                });
                return $dim;
            });
        }
        //
        // eol aggregate all value from children
        return $res;
    }

    private function aggregateRsrValues($res, $hasChildrens)
    {
        // I think we need to do the max aggregate only if doesn't have childs
        $no_dimension_indicators = collect();
        $res['rsr_indicators'] = $res['rsr_indicators']->transform(function ($ind) use ($res, $no_dimension_indicators, $hasChildrens) {
            $customAgg = [];
            if (Str::contains($res['title'], ['UII-1', 'UII-2', 'UII-3'])) {
                $customAgg = $this->rsrOverview->where('project_id', $res['rsr_project_id'])
                        ->where('indicator_title', $ind['title'])
                        ->where('result_title', $res['title']);
            }

            if ($ind['has_dimension']) {
                // collect dimensions value all period
                $periodDimensionValues = $ind['rsr_periods'];
                // custom Agg
                if (Str::contains($res['title'], ['UII-1', 'UII-2', 'UII-3'])) {
                    if(count($customAgg) > 0) {
                        $transformCustomAgg = $customAgg->groupBy('dimension_title')
                            ->map(function($d, $k) {
                                $dimension_values = $d->groupBy('dimension_value_title')
                                    ->map(function($dv, $dvk){
                                        return [
                                            'name' => $dvk,
                                            'total_actual_value' => $dv->sum('max_actual_value'),
                                            'value' => $dv->sum('dimension_target_value')
                                        ];
                                    })->values();
                                return [
                                    'name' => $k,
                                    'total_actual_value' => $dimension_values->sum('total_actual_value'),
                                    'total_target_value' => $dimension_values->sum('value'),
                                    'rsr_dimension_values' => $dimension_values
                                ];
                            })->values();
                        $ind['rsr_dimensions'] = $transformCustomAgg;
                    }

                    if(count($customAgg) === 0) {
                        $periodDimensionValues = $ind['rsr_periods']->sortByDesc('actual_value')->values()->first();
                        $periodDimensionValues = [$periodDimensionValues];
                        $periodDimensionValues = collect($periodDimensionValues)->pluck('rsr_period_dimension_values')->flatten(1);
                        // aggregate dimension value
                        $ind['rsr_dimensions'] = $ind['rsr_dimensions'] = $this->aggregateDimensionValue($ind, $res, $periodDimensionValues, $hasChildrens);
                    }
                } else {
                    $periodDimensionValues = collect($periodDimensionValues)->pluck('rsr_period_dimension_values')->flatten(1);
                    // aggregate dimension value
                    $ind['rsr_dimensions'] = $this->aggregateDimensionValue($ind, $res, $periodDimensionValues, $hasChildrens);
                }
            }

            // $ind['target_value'] = $ind['rsr_periods']->sum('target_value');
            // custom max aggregation for UII 1,2,3
            if (Str::contains($res['title'], ['UII-1', 'UII-2', 'UII-3'])) {
                if ($hasChildrens) {
                    $indicatorIds = $res['childrens']->pluck('rsr_indicators')->flatten(1)->pluck('id');
                    $indActValue = $this->rsrOverview->whereIn('indicator_id', $indicatorIds)->pluck('period_value')->sum();
                    // $indActValue = $res['childrens']->sum('actual_value');
                } else {
                    $indActValue = $ind['rsr_periods']->max('actual_value');
                    if(count($customAgg) > 0) {
                        $indActValue = $customAgg->first()['period_value'];
                    }
                }
            } else {
                $indActValue = $ind['rsr_periods']->sum('actual_value');
            }
            $ind['total_actual_value'] = $indActValue ? $indActValue : 0;

            if (!$ind['has_dimension']) {
                $no_dimension_indicators->push($ind);
            }
            $ind = Arr::except($ind, ['rsr_periods']);
            return $ind;
        });

        $res['rsr_dimensions'] = $res['rsr_indicators']->pluck('rsr_dimensions')->flatten(1);
        $res['total_target_value'] = $res['rsr_indicators']->sum('target_value');
        $res['total_actual_value'] = $res['rsr_indicators']->sum('total_actual_value');
        $res['rsr_indicators_count'] = count($res['rsr_indicators']);
        $res = Arr::except($res, ['rsr_indicators']);
        $res['rsr_indicators'] = $no_dimension_indicators; // separate indicator with no dimension

        return $res;
    }

    private function aggregateDimensionValue($ind, $res, $periodDimensionValues, $hasChildrens)
    {
        $dimensions = $ind['rsr_dimensions']->transform(function ($dim)
            use ($res, $periodDimensionValues, $hasChildrens) {
            $dim['rsr_dimension_values'] = $dim['rsr_dimension_values']->transform(function ($dimVal)
                use ($res, $periodDimensionValues, $hasChildrens, $dim) {
                $periodDimVal = $periodDimensionValues->where('rsr_dimension_value_id', $dimVal['rsr_dimension_value_id']);
                if (Str::contains($res['title'], ['UII-1', 'UII-2', 'UII-3'])) {
                    if ($hasChildrens) {
                        $indicatorIds = $res['childrens']->pluck('rsr_indicators')->flatten(1)->pluck('id');
                        $periodDimVal = $this->rsrOverview->whereIn('indicator_id', $indicatorIds)
                            ->where('result_title', $res['title'])
                            ->where('dimension_title', $dim['name'])
                            ->where('dimension_value_title', $dimVal['name'])
                            ->pluck('period_dimension_actual_value')->sum();
                        // $periodDimVal = $periodDimVal->sum('value');
                    } else {
                        $periodDimVal = $periodDimVal->max('value');
                    }
                } else {
                    $periodDimVal = $periodDimVal->sum('value');
                }
                $dimVal['total_actual_value'] = $periodDimVal ? $periodDimVal : 0;
                return $dimVal;
            });
            return $dim;
        });

        return $dimensions;
    }

    public function reportReactReactCard(Request $request)
    {
        $reachReactId = config('akvo-rsr.charts.reachreact.form_id');
        $datapoints = Datapoint::where('form_id', $reachReactId)->get();
        if (isset($request->country_id) && $request->country_id !== "0") {
            $datapoints = $datapoints->where('country_id', $request->country_id);
        }
        if (isset($request->partnership_id) && $request->partnership_id !== "0") {
            $datapoints = $datapoints->where('partnership_id', $request->partnership_id);
        }
        return [
            "title" => config('akvo-rsr.charts.reachreact.title'),
            "value" => count($datapoints),
        ];
    }

    public function reportReachReactBarChart(Request $request)
    {
        $config = config('akvo-rsr.charts.'.$request->type);
        $question = Question::where('question_id', $config['question_id'])->first();
        $options = Option::where('question_id', $question->id)->get();
        $answers = Answer::where('question_id', $config['question_id'])->with('datapoints')->get();
        if (isset($request->country_id) && $request->country_id !== "0") {
            $answers = $answers->where('datapoints.country_id', $request->country_id);
        }
        if (isset($request->partnership_id) && $request->partnership_id !== "0") {
            $answers = $answers->where('datapoints.partnership_id', $request->partnership_id);
        }
        $data = collect();
        $answers->map(function ($answer) use ($data) {
            $values = str_replace('[', '', $answer['options']);
            $values = str_replace(']', '', $values);
            $values = explode(',', $values);
            foreach ($values as $value) {
                $data->push($value);
            }
            return;
        });
        $results = $data->countBy();
        if (count($results) === 0) {
            return response('no data available', 503);
        };
        $series = $options->map(function($option) use ($results, $request) {
            $name = $option['text'];
            if ($request->type === 'target-audience' && Str::contains($option['text'], '(')) {
                $name = explode('(', $option['text']);
                $name = $name[0];
            }
            return [
                "name" => $name,
                "value" => (isset($results[$option['id']])) ? $results[$option['id']] : 0,
            ];
        })->values();
        $legends = $series->map(function($d){
            return $d['name'];
        });
		$values = $series->map(function($d) {
            return $d['value'];
        });
		return $this->echarts->generateSimpleBarCharts($legends, $values, true, true);
    }

    public function homePartnershipMapChart(Request $request, Partnership $partnerships)
    {
        $data = $partnerships
            ->where('level', 'country')
            ->get()
            ->transform(function($d){
                return array (
                    'name' => $d->name,
                    'value' => $d->childrens->count(),
                );
            });
        $min = collect($data)->min('value');
        $max = collect($data)->max('value');
        return $this->echarts->generateMapCharts($data, $min, $max);
    }

    public function homeSectorDistribution(Request $request, Sector $sectors)
    {
        // $forms = collect(config('surveys.forms'));
        // $list = $forms->pluck('list')->flatten(1);
        // $sector_qids = $list->whereNotNull('sector_qid')->pluck('sector_qid');
        // $data = $answers->whereIn('question_id', $sector_qids)->get();
        // $data = $data->map(function ($d) {
        //     $text = explode('|', $d['text']);
        //     $d['level_1'] = $text[0];
        //     $d['level_2'] = (isset($text[1])) ? $text[1] : null;
        //     return $d;
        // });
        // return $data;
        $data = $sectors
        ->where('level', 'industry')
        ->get()
        ->transform(function ($d) {
            return array (
                'name' => $d->name,
                'value' => $d->childrens->count()
            );
        });
        return $this->echarts->generateDonutCharts($data->pluck('name'), $data);
    }

    public function homePartnershipDistribution(Request $request, Partnership $partnerships)
    {
        $data = $partnerships
        ->where('level', 'country')
        ->get()
        ->transform(function($d){
            return array (
                'name' => $d->name,
                'value' => $d->childrens->count()
            );
        });
        return $this->echarts->generateDonutCharts($data->pluck('name'), $data, true);
    }

    private function getUiiValueByConfig($config, $charts)
    {
        $charts = $charts->values();
        $programId = $config['projects']['parent'];
         $rsrDetail =  \App\RsrDetail::where('project_id', $programId)
            ->whereIn('result_id', [48191,48259])->get();
        $rsrDetail = collect($rsrDetail)->groupBy('result_title')->map(function ($v, $k) use ($charts) {
            $result_id = $v->first()->result_id;
            $custom_name = $charts->where('id', $result_id)->first()['name'];
            $pav = $v->sum('period_actual_value');
            $itv = $v->first()->indicator_target_value;
            return [
                'id' => $result_id,
                'name' => $custom_name ? $custom_name : $k,
                'achieved' => $pav,
                'target' => $itv,
                'toGo' => $itv - $pav
            ];
        });
        return $rsrDetail;
    }

    public function homeInvestmentTracking(Request $request)
    {
        $config = config('akvo-rsr');
        $investment = collect($config['home_charts']['investment_tracking']);
        $results = $this->getUiiValueByConfig($config, $investment);
        $legend = ["Fund to date", "Fund to go"];
        $categories = $results->pluck('name');
        $dataset = collect();
        $dataset->push(["p_achieved", "p_togo", "achieved", "togo", "target", "name"]);
        foreach ($results as $key => $value) {
            $dataset->push(
                [
                    round(($value["achieved"] / $value["target"]) * 100, 3),
                    round(($value["toGo"] / $value["target"]) * 100, 3),
                    $value["achieved"],
                    $value["toGo"],
                    $value["target"],
                    $value["name"]
                ],
            );
        }
        $series = [
            [
                "name" => "Fund to date",
                "type" => "bar",
                "stack" => "investment",
                "label" => [
                    "show" => true,
                    "fontWeight" => "bold",
                    "position" => "insideBottomRight",
                    "formatter" => "{@p_achieved}%"
                ],
                "encode" => [
                    "x" => "achieved",
                    "y" => "name",
                ]
            ],
            [
                "name" => "Fund to go",
                "type" => "bar",
                "stack" => "investment",
                "label" => [
                    "show" => true,
                    "color" => "#a43332",
                    "fontWeight" => "bold",
                    "position" => "insideBottomRight",
                    "formatter" => "{@p_togo}%"
                ],
                "itemStyle" => [
                    "color" => "transparent",
                    "borderType" => "dashed",
                    "borderColor" => "#000"
                ],
                "encode" => [
                    "x" => "togo",
                    "y" => "name",
                ]
            ]
        ];
        $xMax = $results->pluck('target')->max();
        $data = $this->echarts->generateBarCharts($legend, $categories, "Horizontal", $series, $xMax, $dataset);
        return collect($data)->forget('legend');
    }

    public function foodNutritionAndSecurity(Request $request)
    {
        $config = config('akvo-rsr');
        switch ($request->type) {
            case 'food-nutrition-and-security':
                $type = 'food_nutrition_security';
                break;
            case 'private-sector-development':
                $type = 'private_sector_development';
                break;
            case 'input-adittionality':
                $type = 'input_adittionality';
                break;
            default:
                $type = '';
                break;
        }
        $charts = collect($config['impact_react_charts'][$type]);
        $results = $this->getUiiValueByConfig($config, $charts)->sortByDesc(['name']);
        $legend = ["Achieved"];
        $categories = $results->pluck('name');
        $dataset = collect();
        $dataset->push(["p_achieved", "p_togo", "achieved", "togo", "target", "name"]);
        foreach ($results as $key => $value) {
            $dataset->push(
                [
                    round(($value["achieved"] / $value["target"]) * 100, 3),
                    round(($value["toGo"] / $value["target"]) * 100, 3),
                    $value["achieved"],
                    $value["toGo"],
                    $value["target"],
                    $value["name"]
                ],
            );
        }
        $series = [
            [
                "name" => "Achieved",
                "type" => "bar",
                "label" => [
                    "show" => true,
                    "fontWeight" => "bold",
                    "position" => "insideBottomRight",
                    "formatter" => "{@p_achieved}%"
                ],
                "encode" => [
                    "x" => "achieved",
                    "y" => "name",
                ]
            ]
        ];
        $xMax = $results->pluck('target')->max();
        return $this->echarts->generateBarCharts($legend, $categories, "Horizontal", $series, $xMax, $dataset);
    }

    public function reportTotalActivities(Request $request)
    {
        $countries = Partnership::where('level', 'country')
            ->with('country_datapoints')
            ->with('childrens.partnership_datapoints')->get();

        $series = $countries->map(function ($c) {
            $childs = collect($c['childrens'])->map(function ($p) use ($c) {
                return [
                    'name' => $p['name'],
                    'path' => $c['name'].'/'.$p['name'],
                    'value' => count($p['partnership_datapoints']),
                ];
            });
            return [
                'name' => $c['name'],
                'path' => $c['name'],
                'value' => count($c['country_datapoints']),
                'children' => $childs,
            ];
        });
        return $this->echarts->generateTreeMapCharts('Total Activities', $series);
        // $categories = $countries->pluck('name');
        // $series = $countries->map(function ($c) use ($categories) {
        //     return collect($c['childrens'])->map(function ($p) use ($c, $categories) {
        //         $values = collect();
        //         foreach ($categories as $cat) {
        //             if ($c['name'] === $cat) {
        //                 $values->push(count($p['partnership_datapoints']));
        //             } else {
        //                 $values->push(null);
        //             }
        //         }
        //         return [
        //             'name' => $p['name'],
        //             'data' => $values,
        //             'stack' => 'activities',
        //         ];
        //     });
        // })->flatten(1);
        // $type = "Horizontal";
        // $legends = $series->pluck('name');
        // return $this->echarts->generateBarCharts($legends, $categories, $type, $series);
    }

    public function getRsrDatatableByUii(Request $request) {
        $config = config('akvo-rsr');
        // $partnerships = \App\Partnership::get();
        $partnerships = \App\Partnership::where('level', 'partnership')->get();
        $projects = \App\RsrProject::whereIn('partnership_id', $partnerships->pluck('id'))->get();
        // $results = \App\RsrResult::whereIn('rsr_project_id', $projects->pluck('id'))
        $results = \App\RsrResult::where('rsr_project_id', $config['projects']['parent'])
                        ->with('rsr_indicators.rsr_dimensions.rsr_dimension_values')
                        ->with('rsr_indicators.rsr_periods.rsr_period_dimension_values')
                        ->with(['childrens' => function ($q) {
                            $q->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                            $q->with('rsr_indicators.rsr_periods.rsr_period_dimension_values');
                            $q->with(['childrens' => function ($q) {
                                $q->with('rsr_indicators.rsr_dimensions.rsr_dimension_values');
                                $q->with('rsr_indicators.rsr_periods.rsr_period_dimension_values');
                            }]);
                        }])->orderBy('order')->get();

        // return $results;
        $uii = $results->pluck('title');
        $collections = collect();
        $this->fetchChildRsrDatatableByUii($collections, $results);
        $data = $collections->whereIn('id', $projects->pluck('id'))->values();
        // return $data;

        $tmp = collect();
        foreach ($uii as $key) {
            $values = $data->where('uii', $key)->values();
            $test['uii'] = $key;
            foreach ($values as $item) {
                $test[$item['project']] = $item['indicators'];
            }
            $tmp->push($test);
        }

        return [
            'uii' => $uii,
            'partnership' => $projects->pluck('title')->sort()->values()->all(),
            'data' => $tmp,
        ];
    }

    private function fetchChildRsrDatatableByUii($collections, $results)
    {
        foreach ($results as $result) {
            // grep indicators data
            if (count($result['rsr_indicators']) > 0) {
                $indicators = $this->fetchIndicatorRsrDatatableByUii($result['rsr_indicators'], $result);
            }
            $temp = collect([
                'uii' => $result['title'],
                'id' => $result['rsr_project_id'],
                'project' => $result['project'],
                'indicators' => $indicators
            ]);
            // $merge = $temp->merge($indicators->first())->all();
            // $collections->push($merge);
            $collections->push($temp);
            if (count($result['childrens']) > 0) {
                $this->fetchChildRsrDatatableByUii($collections, $result['childrens']);
            }
        }
        return $collections;
    }

    private function fetchIndicatorRsrDatatableByUii($indicators, $result)
    {
        $data = $indicators->map(function ($indicator) use ($result) {
            // project name
            $indicator['project'] = $result['project'];
            // populating the periods
            $periodDimensionValues = [];
            if (count($indicator['rsr_periods']) > 0) {
                $periodDimensionValues = $indicator['rsr_periods']->pluck('rsr_period_dimension_values')->flatten(1);
            }
            // populating the dimension
            $indicator['dimensions'] = null;
            if ($indicator['has_dimension'] || count($indicator['rsr_dimensions']) > 0) {
                $indicator['dimensions'] = $indicator['rsr_dimensions']->map(function ($dimension) use ($periodDimensionValues) {
                    $dimension['values'] = $dimension['rsr_dimension_values']->map(function ($dv) use ($periodDimensionValues) {
                        $dv['actual'] = collect($periodDimensionValues)->where('rsr_dimension_value_id', $dv['id'])->sum();
                        return $dv->only('name', 'value', 'actual');
                    });
                    return $dimension->only('name', 'values');
                });
            }
            return collect($indicator)->only('project', 'title', 'baseline_year', 'baseline_value', 'target_value', 'dimensions', 'periods');
        });
        return $data;
    }

}
