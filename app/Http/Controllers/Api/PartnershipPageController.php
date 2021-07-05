<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Libraries\AkvoRsr;
use App\RsrProject;

class PartnershipPageController extends Controller
{
    private $uiiFilter = ['UII-1', 'UII-2', 'UII-3', 'UII-4', 'UII-5', 'UII-6', 'UII-7', 'UII-8'];
    private $uiiMaxAgg = ['UII-1', 'UII-2', 'UII-3'];

    public function __construct()
    {
        $this->rsr = new AkvoRsr();
    }

    public function getTextVisual(Request $request)
    {
        $project = $this->getRsrProject($request);
        $sector_focus = $this->getTextDataFromFlow($request->partnership_id, 'sector_focus');
        $abc_clusters = $this->getTextDataFromFlow($request->partnership_id, 'abc_names');
        $other_main_partners = $this->getTextDataFromFlow($request->partnership_id, 'other_main_partners');
        $producer_organizations = $this->getTextDataFromFlow($request->partnership_id, 'producer_organization');
        $link = config('akvo-rsr.endpoints.rsr_page').$project['id'];

        $sector_text = $sector_focus->pluck('text')->transform(function ($text) {
            return explode('|', $text)[0];
        })->unique()->values();
        $sector_text = implode(', ', $sector_text->toArray());

        $text = "
            ##partnership_name## project belongs to the ##sector## sector.
            It currently works with ##producer_count## of producer organisations,
            ##abc_count## agri business clusters and ##enterprise_count## enterprises.
            For more details please visit <a target='_blank' href='##rsr_link##'>##rsr_link##</a>
        ";

        $text = str_replace("##partnership_name##", $project['title'], $text);
        $text = str_replace("##sector##", $sector_text, $text);
        $text = str_replace("##producer_count##", count($producer_organizations), $text);
        $text = str_replace("##abc_count##", count($abc_clusters), $text);
        $text = str_replace("##enterprise_count##", count($other_main_partners), $text);
        $text = str_replace("##rsr_link##", $link, $text);
        return $text;
    }

    public function getImplementingPartner(Request $request)
    {
        $project = $this->getRsrProject($request);
        $results = $this->fetchRsrData('partnership', $project['id'])->flatten(1);
        return $results;
    }

    public function getResultFramework(Request $request)
    {
        $project = $this->getRsrProject($request);
        $results = $this->fetchRsrData('results', $project['id'])->flatten(1);
        $results = $results->filter(function ($res) {
            // * Filter result to show only UII 1 - 8
            return Str::contains($res['title'], $this->uiiFilter);
        })->values()->transform(function ($res) {
            // * Max Agg for UII 1 - 3
            if (Str::contains($res['title'], $this->uiiMaxAgg)) {
                $res['indicators'] = collect($res['indicators'])->transform(function ($ind) {
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
                                $dimVal['target_value'] = $dimValTarget['value'];
                                $dimVal['actual_value'] = $dimValActual['value'] ? $dimValActual['value'] : 0;
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
                $res['indicators'] = collect($res['indicators'])->transform(function ($ind) {
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
                                $dimVal['target_value'] = $dimValTarget['value'];
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
        });

        return $results;
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
