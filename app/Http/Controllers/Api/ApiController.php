<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\SectorIndustry;
use App\Datapoint;
use App\RnrGender;
use App\Partnership;
use App\RsrResult;
use App\RsrDetail;
use App\RsrMaxCustomValues;

class ApiController extends Controller
{

    private function getRequestAttributes($request)
    {
        $sum = collect(explode(",", $request->sum))
            ->map(function($x){
                return str_replace(" ","_",$x);
            })->filter()->all();
        $req = collect([])
            ->merge(explode(",", $request->indicators))
            ->merge(explode(",", $request->sum));
        $params = collect([]);
        $customParams = collect([]);
        $req->each(function($x) use ($params, $customParams){
            if (Str::contains($x, " ")) {
                $customParams->push(explode(" ", $x));
                collect(explode(" ", $x))->each(function($n) use ($params) {
                    $params->push($n);
                });
            } else if ($x === "year") {
                $params->push("event_date");
            } else if ($x === "month") {
                $params->push("event_date");
            } else if ($x === "year_month") {
                $params->push("event_date");
            } else {
                $params->push($x);
            }
            return;
        });
        return [$sum,$params,$customParams];
    }

    private function appendCustomParams($data, $customParams) {
        return $data->transform(function($d) use ($customParams){
            $customParams->each(function($c) use ($d) {
                $n = collect();
                collect($c)->each(function($e) use ($d, $n){
                    $n->push($d[$e]);
                });
                $d[implode("_", $c)] = implode(" ", $n->toArray());
            });
            return $d;
        });
    }

    public function getPartnership(Request $request)
    {
        $allPartnership = $this->getPartnershipCache();
        $partnership = $allPartnership->where('level', 'country')
            ->map(function ($p) use ($allPartnership) {
                $childs = $allPartnership->where('parent_id', $p->id)->values();
                $p['value'] = $childs->count();
                $p['childrens'] = $childs->map(function ($c) {
                    return collect($c)->only('id', 'name');
                });
                return collect($p)->only('id', 'name', 'value', 'childrens');
            })->values();
        return $partnership;
    }

    public function getSector(Request $request, SectorIndustry $si)
    {
        $partnership = $this->getPartnershipCache();
        [$sum, $params, $customParams] = $this->getRequestAttributes($request);
        if ($request->country) {
            $si = $si->whereIn('country_id', explode(",", $request->country));
            $params->push('country_id');
        }
        if ($request->partnership) {
            $si = $si->whereIn('partnership_id', explode(",", $request->partnership));
            $params->push('partnership_id');
        }
        if ($request->form_id) {
            $si = $si->whereIn('form_id', explode(",", $request->form_id));
            $params->push('form_id');
        }
        $params = $params->filter()->unique()->toArray();
        $si = $si->get($params);
        if ($customParams->count()) {
            $si = $this->appendCustomParams($si, $customParams);
        }
        if (isset($request->sum)) {
            return $this->countBy($si, $partnership, $sum);
        }
        return $si;
    }

    public function getRnrGender(Request $request, RnrGender $rnr)
    {
        $partnership = $this->getPartnershipCache();
        [$sum, $params, $customParams] = $this->getRequestAttributes($request);
        $params->push('total');
        if ($request->country) {
            $rnr = $rnr->whereIn('country_id', explode(",", $request->country));
            $params->push('country_id');
        }
        if ($request->partnership) {
            $rnr = $rnr->whereIn('partnership_id', explode(",", $request->partnership));
            $params->push('partnership_id');
        }
        if ($request->start && $request->end) {
            $rnr = $rnr->whereBetween('event_date', [date($start), date($end)]);
            $params->push('event_date');
        }
        $params = $params->filter()->unique()->toArray();
        $rnr = $rnr->get($params);
        if ($customParams->count()) {
            $rnr = $this->appendCustomParams($rnr, $customParams);
        }
        if (isset($request->sum)) {
            return $this->sumBy($rnr, $partnership, $sum);
        }
        return $rnr;
    }

    public function getPartnershipCommodities(Request $request)
    {
        $surveys = collect(config('surveys.forms'))->where('name', 'Organisation Forms')->first();
        $formIds = collect($surveys['list'])->pluck('form_id');
        $partnership = $this->getPartnershipCache();
        $datapoint = $this->filterPartnership($request, Datapoint::whereIn('form_id', $formIds)->get());
        $results = $datapoint->map(function ($d) use ($partnership) {
            return $d;
        });
        return $results;
    }

    private function sumRsr($data, $groups, $index = 0) {
        $data = collect($data)
            ->groupBy($groups[$index]."_id")->map(function($d, $k) use ($groups, $index) {
                $child = $index + 1;
                $title = $d[0][$groups[$index].'_title'];
                $name = $groups[$index];
                if (count($groups) > $child){
                    $data = $this->sumRsr($d, $groups, $child);
                    return [
                        'name' => $title,
                        'target_value' => $d[0][$name.'_target_value'],
                        'actual_value' => $d->sum($name.'_actual_value'),
                        'value' => $d->sum($name.'_value'),
                        'childrens' => $data,
                        'stack' => $groups[$index]
                    ];
                }
                return [
                    'name' => $title,
                    'target_value' => $d[0][$name.'_target_value'],
                    'actual_value' => $d->sum($name.'_actual_value'),
                    'value' => $d->sum($name.'_value'),
                ];
            })->values();
        return $data;
    }


    public function getRsrImpactReach(Request $request, RsrDetail $rsrDetail)
    {
        $config = config('akvo-rsr');
        $charts = collect($config['impact_react_charts']);
        $programId = $config['projects']['parent'];
        $rsrDetail = $rsrDetail->where('project_id',$programId)
                               ->whereNotNull('dimension_id')
                               ->get();
        $groups = ['result', 'period', 'dimension'];
        return $this->sumRsr($rsrDetail,$groups);

    }

    public function getRsrUiiReportAfter(Request $request)
    {
        $config = config('akvo-rsr');
        $charts = collect($config['impact_react_charts']);
        $programId = $config['projects']['parent'];
        return [];

    }

    public function getRsrUiiReport(Request $request, RsrDetail $rsrDetail)
    {
        /*
        $rsrUiiReport = Cache::get('rsr-uii-report');
        if ($rsrUiiReport) {
            return $rsrUiiReport;
        }
         */

        $config = config('akvo-rsr');
        $charts = collect($config['impact_react_charts']);
        $programId = $config['projects']['parent'];

        $rsrResults = RsrResult::where('rsr_project_id', $programId)
            ->with('rsr_indicators.rsr_dimensions.rsr_dimension_values')
            ->with('rsr_indicators.rsr_periods.rsr_period_dimension_values')
            ->get();

        // UII-1, UII-2, UII-3
        $customAgg = RsrMaxCustomValues::get()
            ->groupBy('result_title')
            ->map(function($data, $key) {
                $dimensions = $data->whereNotNull('dimension_value_title')
                                     ->groupBy('dimension_title')
                                     ->map(function($d, $k) {
                                         $dimension_values = $d
                                             ->groupBy('dimension_value_title')
                                             ->map(function($dv, $dvk){
                                                 return [
                                                     'name' => $this->transformDimensionValueName($dvk),
                                                     'actual_value' => $dv->sum('max_actual_value')
                                                 ];
                                             })->values();
                                         return [
                                             'name' => $k,
                                             'actual_value' => $dimension_values->sum('actual_value'),
                                             'values' => $dimension_values
                                         ];
                                     })->values();
                $actual_value = $dimensions->sum('actual_value');
                if (!count($dimensions)){
                    $actual_value = $data->sum('max_period_value');
                };
                return [
                    'uii' => Str::beforeLast($key, ':'),
                    'dimensions' => $dimensions,
                    'actual_value' => $actual_value
                ];
            })->values();

        $results = $rsrResults->map(function ($rs) use ($charts, $customAgg) {
            $chart = $charts->where('id', $rs['id'])->first();
            $rs['rsr_indicators'] = $rs->rsr_indicators->transform(function ($ind) use ($chart) {
                if ($ind['has_dimension']) {
                    $ind['rsr_dimensions'] = $ind->rsr_dimensions->transform(function ($dim) use ($ind, $chart) {
                        $dimVal = $dim->rsr_dimension_values->map(function ($dv) use ($ind) {
                            $actualDimValues = $ind['rsr_periods']->pluck('rsr_period_dimension_values')
                                ->flatten(1)->where('rsr_dimension_value_id', $dv['id']);
                            return [
                                'name' => $this->transformDimensionValueName($dv['name']),
                                'target_value' => $dv['value'],
                                'actual_value' => $actualDimValues->sum('value')
                            ];
                        });

                        $text = null;
                        $order = null;
                        if (isset($chart['dimensions'])) {
                            foreach ($chart['dimensions'] as $key => $item) {
                                if (Str::contains($dim['name'], $item['dimension'])) {
                                    $text = $item['target_text'];
                                    $order = $item['order'];
                                }
                            }
                        }

                        return [
                            'name' => $dim['name'],
                            'target_text' => $text,
                            'order' => $order,
                            'values' => $dimVal
                        ];
                    });

                    $ind['rsr_periods'] = $ind->rsr_periods->transform(function ($p) {
                        $p['actual_value'] = $p['rsr_period_dimension_values']->sum('value');
                        return $p;
                    });
                }

                if (Str::contains($ind['title'], "Total value(Euros) of financial services")) {
                    $ind['rsr_dimensions'] = collect($ind['rsr_dimensions'])->push(
                        [
                            'name' => $ind['title'],
                            'target_text' => '##number## Euros as value of additional financial services.',
                            'order' => 1,
                            'values' => [],
                            'target_value' => $ind['target_value'],
                            'actual_value' => $ind['rsr_periods']->sum('actual_value')
                        ]
                    );
                }
                $ind['actual_value'] = $ind['rsr_periods']->sum('actual_value');
                return $ind;
            });

            $uii = Str::before($rs['title'],":");

            if ($chart['max']) {
                $agg = $customAgg->where('uii', $uii)->first();
                return [
                    "group" => $chart['group'],
                    "uii" => $uii,
                    "target_text" => $chart['target_text'],
                    "target_value" => $rs['rsr_indicators']->sum('target_value'),
                    "actual_value" => $agg['actual_value'],
                    "dimensions" => $rs['rsr_indicators']->pluck('rsr_dimensions')
                                        ->flatten(1)->map(function ($d) use ($agg) {
                                            $match = $agg['dimensions']->where('name', $d['name'])->first();
                                            $d['actual_value'] = $match['actual_value'];
                                            $d['values'] = $d['values']->map(function ($v) use ($match) {
                                                $v['actual_value'] = $match['values']
                                                    ->where('name', $v['name'])->first()['actual_value'];
                                                return $v;
                                            });
                                            return $d;
                                        })
                ];
            }

            $dimensions = $rs['rsr_indicators']->pluck('rsr_dimensions')->flatten(1);

            if ($chart['orders']) {
                $dimensions = $dimensions->sortBy('order')->values();
            }

            if (Str::contains($uii, "UII-8")) {
                $dimensions = $dimensions->map(function($d){
                    $d['name'] = $this->transformDimensionName($d['name']);
                    return $d;
                });
            }

            $target_value = $rs['rsr_indicators']->sum('target_value');
            $actual_value = $rs['rsr_indicators']->sum('actual_value');
            $target_text = $chart['target_text'];

            if ($chart['replace_value_with']) {
                $replace_value = $dimensions->where('order', $chart['replace_value_with'])->first();
                $target_value = $replace_value['target_value'];
                $target_text = $replace_value['target_text'];
                $actual_value = $replace_value['actual_value'];
            }

            return [
                "group" => $chart['group'],
                "uii" => $uii,
                "target_text" => $target_text,
                "target_value" => $target_value,
                "actual_value" => $actual_value,
                "dimensions" => $dimensions
            ];})->groupBy('group')->map(function ($res, $key) {
            return [
                "group" => $key,
                "childrens" => $res
            ];
        })->values();

        Cache::put('rsr-uii-report', $results, 86400);
        return $results;
    }

    private function transformDimensionName($name)
    {
        if (Str::contains($name, "Newly registered SHFs")){
            return "250,000 smallholder farmers (50% women and 40% youth) have access to additional financial services.";
        }
        if (Str::contains($name, "Newly registered micro-entrepreneurs")){
            return "2,000 MSMEs (50% female-led; 20% young entrepreneurs) have access to additional financial services.";
        }
        if (Str::contains($name, "Newly registered SMEs")){
            return "125 SMEs (50% female-led) have access to additional financial services.";
        }
        if (Str::contains($name, "Total value(Euros) of financial services accessed by the SHFs, micro-entrepreneurs and SMEs")){
            return "50,000,000 Euros as value of additional financial services.";
        }
        return $name;
    }

    private function transformDimensionValueName($name)
    {
        if (!Str::contains($name, ">") && !Str::contains($name, "<")) {
            if (Str::contains($name, "Male")) {
                $name = "Male";
            }
            if (Str::contains($name, "Female")) {
                $name = "Female";
            }
        }
        if (Str::contains($name, ">") || Str::contains($name, "<")) {
            if (Str::contains($name, "Male") && Str::contains($name, ">")) {
                $name = "Senior Male";
            }
            if (Str::contains($name, "Male") && Str::contains($name, "<")) {
                $name = "Junior Male";
            }
            if (Str::contains($name, "Female") && Str::contains($name, ">")) {
                $name = "Senior Female";
            }
            if (Str::contains($name, "Female") && Str::contains($name, "<")) {
                $name = "Junior Female";
            }
        }
        return $name;
    }

    private function getPartnershipCache() {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }
        return $partnership;
    }

    private function filterPartnership($request, $data)
    {
        $country_id = $request->country_id;
        $partnership_id = $request->partnership_id;
        $start = $request->start;
        $end = $request->end;
        if ($country_id !== "0") {
            $data = $data->where('country_id', $country_id);
        }
        if ($partnership_id !== "0") {
            $data = $data->where('partnership_id', $partnership_id);
        }
        if ($start !== "0") {
            $data = $data->whereBetween('submission_date', [date($start), date($end)]);
        }
        return $data;
    }

    private function sumBy($data, $partnership, $groups, $index = 0) {
        $data = collect($data)
            ->groupBy($groups[$index])->map(function($d, $k) use ($partnership, $groups, $index) {
                $child = $index + 1;
                if (in_array($groups[$index],["country_id","partnership_id"])) {
                    $k= $partnership->where('id', $k)->first()->name;
                }
                if (count($groups) > $child){
                    $data = $this->sumBy($d, $partnership, $groups, $child);
                    return [
                        'name' => $k,
                        'value' => $d->sum('total'),
                        'childrens' => $data,
                        'stack' => $groups[$index]
                    ];
                }
                return [
                    'name' => $k,
                    'value' => $d->sum('total')
                ];
            })->values();
        return $data;
    }

    private function countBy($data, $partnership, $groups, $index = 0, $counter='datapoint_id') {
        $data = collect($data)
            ->groupBy($groups[$index])->map(function($d, $k) use ($partnership, $groups, $index, $counter) {
                $child = $index + 1;
                if (in_array($groups[$index],["country_id","partnership_id"])) {
                    $k= $partnership->where('id', $k)->first()->name;
                }
                if (count($groups) > $child){
                    $data = $this->countBy($d, $partnership, $groups, $child, $counter);
                    return [
                        'name' => $k,
                        'value' => $d->count($counter),
                        'childrens' => $data,
                        'stack' => $groups[$index]
                    ];
                }
                return [
                    'name' => $k,
                    'value' => $d->count($counter)
                ];
            })->values();
        return $data;
    }
}
