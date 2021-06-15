<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Sector;
use App\Datapoint;
use App\RnrGender;
use App\Partnership;
use App\RsrResult;

class TestController extends Controller
{
    private function getPartnershipCache() {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }
        return $partnership;
    }

    private function filterRnrGenderData($request, $data)
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
            $data = $data->whereBetween('event_date', [date($start), date($end)]);
        }
        return $data;
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

    public function getSector(Request $request)
    {
        $sector = Sector::where('level', 'industry')->with('childrens')->get();
        $sector = $sector->map(function ($s) {
            $s['value'] = $s->childrens->count();
            $s['childrens'] = $s->childrens->transform(function ($c) {
                return collect($c)->only('id', 'name');
            });
            return collect($s)->only('id', 'name', 'value', 'childrens');
        });
        return $sector;
    }

    public function getRnrGender(Request $request)
    {
        $partnership = $this->getPartnershipCache();
        $rnrGender = $this->filterRnrGenderData($request, RnrGender::all());

        $genderGroupByCountryAndCategories = $rnrGender->map(function ($rnr) {
            $rnr['event_year'] = date("Y", strtotime($rnr->event_date));
            return $rnr;
        })->groupBy('country_id')
            ->map(function ($country, $key) use ($partnership) {
                $category = $country->groupBy('question_id')
                            ->map(function ($d, $key) {
                                $years = $d->groupBy('event_year')
                                    ->map(function ($y, $key) {
                                        return [
                                            'year' => $key,
                                            'value' => $y->sum('total')
                                        ];
                                    })->sortBy('year')->values();;
                                return [
                                    'gender' => $d->first()->gender,
                                    'age' => $d->first()->age,
                                    'value' => $d->sum('total'),
                                    'per_year' => $years
                                ];
                            })->values();
                return [
                    'country_id' => $key,
                    'country' => $partnership->where('id', $key)->first()->name,
                    'value' => $category->sum('value'),
                    'categories' => $category,
                ];
            })->values();

        $results = $partnership->where('level', 'country')->values()
            ->map(function ($p) use ($genderGroupByCountryAndCategories) {
                $find = $genderGroupByCountryAndCategories->where('country_id', $p->id)->first();
                if (!$find) {
                    return [
                        'country' =>$p->name,
                        'value' => 0,
                        'categories' => []
                    ];
                }
                return collect($find)->except('country_id');
            });

        return $results;
    }

    public function getRnrGenderGroupByYear(Request $request)
    {
        $partnership = $this->getPartnershipCache();
        $rnrGender = $this->filterRnrGenderData($request, RnrGender::all());

        $groupDataByCountryAndYear = $rnrGender->map(function ($rnr) {
            $rnr['event_year'] = date("Y", strtotime($rnr->event_date));
            return $rnr;
        })->groupBy('country_id')->map(function ($c, $key) use ($partnership) {
            $years = $c->groupBy('event_year')
                    ->map(function ($y, $key) {
                        return [
                            'year' => $key,
                            'total' => $y->sum('total')
                        ];
                    })->sortBy('year')->values();
            return [
                'country_id' => $key,
                'country' => $partnership->where('id', $key)->first()->name,
                'total' => $years,
            ];
        })->values();

        $results = $partnership->where('level', 'country')->values()
            ->map(function ($p) use ($groupDataByCountryAndYear) {
                $find = $groupDataByCountryAndYear->where('country_id', $p->id)->first();
                if (!$find) {
                    return [
                        'country' =>$p->name,
                        'total' => 0,
                        'years' => []
                    ];
                }
                return collect($find)->except('country_id');
            });

        return $results;
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

    public function getRsrUiiReport(Request $request)
    {
        $config = config('akvo-rsr');
        $charts = collect($config['impact_react_charts']);
        $programId = $config['projects']['parent'];

        $rsrResults = RsrResult::where('rsr_project_id', $programId)
            ->with('rsr_indicators.rsr_dimensions.rsr_dimension_values')
            ->with('rsr_indicators.rsr_periods.rsr_period_dimension_values')
            ->get();

        $results = $rsrResults->map(function ($rs) use ($charts) {
            $rs['rsr_indicators'] = $rs->rsr_indicators->transform(function ($ind) {
                if ($ind['has_dimension']) {
                    $ind['rsr_dimensions'] = $ind->rsr_dimensions->transform(function ($dim) use ($ind) {
                        $dimVal = $dim->rsr_dimension_values->map(function ($dv) use ($ind) {
                            $actualDimValues = $ind['rsr_periods']->pluck('rsr_period_dimension_values')
                                ->flatten(1)->where('rsr_dimension_value_id', $dv['id']);

                            $name = $dv['name'];
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

                            return [
                                'name' => $name,
                                'target_value' => $dv['value'],
                                'actual_value' => $actualDimValues->sum('value')
                            ];
                        });
                        return [
                            'name' => $dim['name'],
                            'values' => $dimVal
                        ];
                    });

                    $ind['rsr_periods'] = $ind->rsr_periods->transform(function ($p) {
                        $p['actual_value'] = $p['rsr_period_dimension_values']->sum('value');
                        return $p;
                    });
                }
                $ind['actual_value'] = $ind['rsr_periods']->sum('actual_value');
                return $ind;
            });
            $chart = $charts->where('id', $rs['id'])->first();
            return [
                "group" => $chart['group'],
                "title" => Str::after($rs['title'], ": "),
                "target_value" => $rs['rsr_indicators']->sum('target_value'),
                "actual_value" => $rs['rsr_indicators']->sum('actual_value'),
                "dimensions" => $rs['rsr_indicators']->pluck('rsr_dimensions')->flatten(1)
            ];
        });

        return $results;
    }
}
