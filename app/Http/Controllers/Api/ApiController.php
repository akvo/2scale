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

    public function getRsrUiiReport(Request $request, RsrDetail $rsrDetail)
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
