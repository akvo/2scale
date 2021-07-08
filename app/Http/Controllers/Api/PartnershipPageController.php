<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Libraries\AkvoRsr;
use App\RsrProject;
use App\Http\Controllers\Controller\Api\ApiController;

class PartnershipPageController extends Controller
{
    private $uiiFilter = ['UII-1', 'UII-2', 'UII-3', 'UII-4', 'UII-5', 'UII-6', 'UII-7', 'UII-8', 'Private sector contribution', '2SCALE\'s Contribution'];
    private $uiiMaxAgg = ['UII-1', 'UII-2', 'UII-3'];

    public function __construct()
    {
        $this->rsr = new AkvoRsr();
    }

    public function getTextVisual(Request $request)
    {
        $project = $this->getRsrProject($request);
        if (!$project) {
            return response('no data available', 503);
        }

        $sector_focus = $this->getTextDataFromFlow($request->partnership_id, 'sector_focus');
        $abc_clusters = $this->getTextDataFromFlow($request->partnership_id, 'abc_names');
        $other_main_partners = $this->getTextDataFromFlow($request->partnership_id, 'other_main_partners');
        $producer_organizations = $this->getTextDataFromFlow($request->partnership_id, 'producer_organization');
        $link = config('akvo-rsr.endpoints.rsr_page').$project['id'];

        $sector_text = $sector_focus->pluck('text')->transform(function ($text) {
            return explode('|', $text)[0];
        })->unique()->values();
        $sector_text = implode(', ', $sector_text->toArray());

        return [
            'title' => $this->getPartnershipName($project['title']),
            'sector' => $sector_text,
            'producer' => count($producer_organizations),
            'abc' => count($abc_clusters),
            'enterprise' => count($other_main_partners),
            'link' => $link,
        ];
    }

    public function getImplementingPartner(Request $request)
    {
        $project = $this->getRsrProject($request);
        if (!$project) {
            return response('no data available', 503);
        }
        $results = $this->fetchRsrData('partnership', $project['id'])->flatten(1);
        $results = $results->reject(function ($res) {
            return !Str::contains(strtolower($res['organisation_role_label']), 'implementing');
        });

        return $results;
    }

    public function getResultFramework(Request $request)
    {
        $project = $this->getRsrProject($request);
        if (!$project) {
            return response('no data available', 503);
        }

        $charts = config('partnership-page.impact_charts');
        $results = $this->fetchRsrData('results', $project['id'])->flatten(1);

        // * Transform results value
        $results = $results->filter(function ($res) {
            // * Filter result to show only UII 1 - 8
            return Str::contains($res['title'], $this->uiiFilter);
        })->values()->transform(function ($res) {
            // * Max Agg for UII 1 - 3
            if (Str::contains($res['title'], $this->uiiMaxAgg)) {
                $res['indicators'] = collect($res['indicators'])->transform(function ($ind) use ($res) {
                    $ind['uii'] = $res['title'];
                    $maxPeriod = collect($ind['periods'])->sortByDesc('actual_value')->values()->first();
                    // * doesn't has dimensions
                    $ind['dimensions'] = [];
                    $ind['actual_value'] = floatVal($maxPeriod['actual_value']);
                    // * has dimensions
                    if (count ($ind['dimension_names']) > 0) {
                        $ind['dimensions'] = collect($ind['dimension_names'])->map(function ($dim) use ($ind, $maxPeriod) {
                            $dim['values'] = collect($dim['values'])->map(function ($dimVal) use ($ind, $maxPeriod) {
                                $dimValTarget = collect($ind['disaggregation_targets'])->where('dimension_value', $dimVal['id'])->first();
                                $dimValActual = collect($maxPeriod['disaggregations'])->filter(function ($max) use ($dimVal) {
                                    return $max['dimension_name']['id'] === $dimVal['name'] && $max['dimension_value']['id'] === $dimVal['id'];
                                })->values()->first();
                                $dimVal['target_value'] = $dimValTarget ? $dimValTarget['value'] ? $dimValTarget['value'] : 0 : 0;
                                $dimVal['actual_value'] = $dimValActual ? $dimValActual['value'] : 0;
                                return $dimVal;
                            });
                            $dim['target_value'] = $dim['values']->sum('target_value');
                            $dim['actual_value'] = $dim['values']->sum('actual_value');
                            return $dim;
                        });
                    }
                    return $ind;
                });
            } else {
                // * Sum Agg for UII 4 - 8
                $res['indicators'] = collect($res['indicators'])->transform(function ($ind) use ($res) {
                    $ind['uii'] = $res['title'];
                    $periods = collect($ind['periods'])->values();
                    // * doesn't has dimensions
                    $ind['dimensions'] = [];
                    $ind['actual_value'] = $periods->pluck('actual_value')
                    ->transform(function ($val) {
                        return floatVal($val);
                    })->sum();
                    // * has dimensions
                    if (count ($ind['dimension_names']) > 0) {
                        $ind['dimensions'] = collect($ind['dimension_names'])->map(function ($dim) use ($ind, $periods) {
                            $dim['values'] = collect($dim['values'])->map(function ($dimVal) use ($ind, $periods) {
                                $dimValTarget = collect($ind['disaggregation_targets'])->where('dimension_value', $dimVal['id'])->first();
                                $dimValActual = collect($periods->pluck('disaggregations')->flatten(1))->filter(function ($max) use ($dimVal) {
                                    return $max['dimension_name']['id'] === $dimVal['name'] && $max['dimension_value']['id'] === $dimVal['id'];
                                })->values();
                                $dimVal['target_value'] = $dimValTarget ? $dimValTarget['value'] ? $dimValTarget['value'] : 0 : 0;
                                $dimVal['actual_value'] = $dimValActual ? $dimValActual->sum('value') : 0;
                                return $dimVal;
                            });
                            $dim['target_value'] = $dim['values']->sum('target_value');
                            $dim['actual_value'] = $dim['values']->sum('actual_value');
                            return $dim;
                        });
                    }
                    return $ind;
                });
            }
            return $res;
        })->pluck('indicators')->flatten(1);

        // * Put results value into chart config
        $charts = collect($charts)->map(function ($chart) use ($results) {
            $result = $results->filter(function ($res) use ($chart) {
                return Str::contains($res['uii'], $chart['id']);
            })->values();
            if (!isset($chart['dimensions'])) {
                $result = $result->first();
                $chart['target_value'] = $result['target_value'];
                $chart['actual_value'] = $result['actual_value'];
                $chart['dimensions'] = collect($result['dimensions'])->transform(function ($dim) {
                    return [
                        'name' => $dim['name'],
                        'target_text' => null,
                        'order' => null,
                        'target_value' => $dim['target_value'],
                        'actual_value' => $dim['actual_value'],
                        'values' => collect($dim['values'])->transform(function ($d) {
                            return [
                                'name' => $this->transformDimensionValueName($d['value']),
                                'target_value' => $d['target_value'],
                                'actual_value' => $d['actual_value'],
                            ];
                        }),
                    ];
                });
            } else if (isset($chart['dimensions'])) {
                $replaceValue = collect($chart['dimensions'])->where('order', $chart['replace_value_with'])->first();
                $total = $result->filter(function ($res) use ($replaceValue) {
                    return Str::contains($res['title'], $replaceValue['dimension']);
                })->values()->first();
                $chart['target_value'] = $total['target_value'];
                $chart['actual_value'] = $total['actual_value'];
                $chart['dimensions'] = collect($chart['dimensions'])->transform(function ($dim) use ($result, $chart) {
                    $res = $result->filter(function ($res) use ($dim, $chart) {
                        return Str::contains($res['title'], $dim['dimension']);
                    })->values()->first();
                    if (count($res['dimensions']) > 0) {
                        return [
                            'name' => $res['title'],
                            'target_text' => $dim['target_text'],
                            'order' => $dim['order'],
                            'values' => $res['dimensions']->pluck('values')->flatten(1)->transform(function ($d) {
                                return [
                                    'name' => $this->transformDimensionValueName($d['value']),
                                    'target_value' => $d['target_value'],
                                    'actual_value' => $d['actual_value'],
                                ];
                            }),
                            'target_value' => $res['target_value'] ? $res['target_value'] : 0,
                            'actual_value' => $res['actual_value']
                        ];;
                    } else {
                        return [
                            'name' => $res['title'],
                            'target_text' => $dim['target_text'],
                            'order' => $dim['order'],
                            'values' => $res['dimensions'],
                            'target_value' => $res['target_value'] ? $res['target_value'] : 0,
                            'actual_value' => $res['actual_value']
                        ];
                    }
                });
            }

            return [
                'group' => $chart['group'],
                'uii' => $chart['name'],
                'target_text' => $chart['target_text'],
                'target_value' => $chart['target_value'],
                'actual_value' => $chart['actual_value'],
                'dimensions' => $chart['dimensions'],
            ];
        })->reject(function ($c) {
            return $c['actual_value'] <= floatVal(0);
        })->groupBy('group')->transform(function ($g, $k) {
            return [
                'group' => $k,
                'childrens' => $g
            ];
        })->values();

        return $charts;
    }

    public function getPartnershipName($pname)
    {
        $names = collect(config('partnership-page.partnership_names'));
        $code = explode("_", $pname)[0];
        $name = isset($names[$code]) ? $names[$code] : null;
        $name = $name ? $name : $pname;
        return $name;
    }

    private function getRsrProject($request)
    {
        $project = RsrProject::where('partnership_id', $request->partnership_id)->first();
        return $project;
    }

    private function getTextDataFromFlow($partnershipId, $type)
    {
        $config = config('partnership-page.text_visual');
        $config = $config[$type];
        switch ($type) {
            case 'abc_names':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['cluster_qid'];
                break;
            case 'other_main_partners':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['enterprise_qid'];
                break;
            case 'producer_organization':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['producer_organization_qid'];
                break;
            case 'sector_focus':
                $partnershipQid = $config['qids']['partnership_qid'];
                $typeQid = $config['qids']['sector_qid'];
                break;
            default:
                $partnershipQid = null;
                $typeQid = null;
                break;
        }
        $partnership_answers = \App\Answer::where('question_id', $partnershipQid)->get();
        if ($partnershipId) {
            $partnership = \App\Partnership::find($partnershipId);
            $partnership_answers = $partnership_answers->filter(function ($answer) use ($partnership) {
                return str_contains(strtolower($answer['text']), strtolower($partnership->name));
            });
        }
        $datapoints_answers = $partnership_answers->pluck('datapoint_id');
        $type_answers = \App\Answer::where('question_id', $typeQid)
                            ->whereIn('datapoint_id', $datapoints_answers)
                            ->get()->map(function ($item) {
                                $item['text'] = trim($item['text']);
                                return $item;
                            });
        return $type_answers;
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

    private function fetchRsrData($endpoint, $projectId)
    {
        $data = collect();
        $results = $this->rsr->get($endpoint, 'project', $projectId);
        if ($results['count'] == 0) {
            return [];
        }
        $data->push($results['results']);
        // fetch next page
        while($results['next'] !== null){
            $results = $this->rsr->fetch($results['next']);
            if ($results['count'] !== 0) {
                $data->push($results['results']);
            }
        }
        return $data;
    }
}
