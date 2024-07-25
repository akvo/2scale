<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Libraries\AkvoRsr;
use App\Partnership;
use App\RsrProject;
use App\RsrResult;
use App\RsrIndicator;
use App\RsrPeriod;
use App\RsrDimension;
use App\RsrDimensionValue;
use App\RsrPeriodDimensionValue;
use App\RsrPeriodData;
use App\RsrTitle;
use App\RsrTitleable;
use App\LastSync;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class RsrSeedController extends Controller
{
    public function __construct()
    {
        $this->rsr = new AkvoRsr();
        $this->collections = collect();
        $this->periods = collect();
        $this->periodData = collect();
        $this->dimensions = collect();
    }

    public function seedRsr(
        Partnership $partnership, RsrProject $project, RsrResult $result, RsrIndicator $indicator,
        RsrPeriod $period, RsrDimension $dimension, RsrDimensionValue $dimensionValue,
        RsrPeriodDimensionValue $periodDimensionValue, RsrPeriodData $periodData,
        Request $request
    )
    {
        $this->seedRsrProjects($partnership, $project, $request);
        $this->seedRsrResults($project, $result, $indicator, $period,  $dimension,  $dimensionValue, $periodDimensionValue, $periodData, $request);
        $lastSync = LastSync::updateOrCreate(['id' => 1], ['date' => now()]);
        return "Done";
    }

    public function seedRsrProjects(Partnership $partnership, RsrProject $project, Request $request)
    {
        $this->collections = collect();
        $parentId = config('akvo-rsr.projects.parent');
        $parent = $this->getProject($parentId);
        $parent['partnership_id'] = null;
        $this->collections->push($parent);

        $partnerships = $partnership->with('parents')->get();
        if (isset($request->total_batch) && isset($request->batch)) {
            $batch = (int) $request->batch;
            $totalBatch = (int) $request->total_batch;

            // start batch
            $count = count(\App\Partnership::all());
            $start = 1;
            $end =  intdiv($count, $totalBatch);

            // query project per batch
            if ($batch > 1) {
                $start = ($end * ($batch - 1)) + 1;
                $end = ($end * $batch);
                $end = ($totalBatch === $batch) ? $end + ($count % $totalBatch) : $end;
            }
            $partnerships = $partnerships->whereBetween('id', [$start, $end]);
        }

        $partnerships->each(function ($val) {
            $config = $val['code'].'.parent';
            if ($val['parent_id'] !== null) {
                $config = $val['parents']['code'].'.childs.'.$val['code'];
            }
            $projectId = config('akvo-rsr.projects.childs.'.$config);

            if ($projectId !== null) {
                $data = $this->getProject($projectId);
                $data['partnership_id'] = $val['id'];
                $this->collections->push($data);
            }
        });

        $projects = $this->collections->map(function ($val) use ($project) {
            if (!isset($val['id'])) {
                return $val;
            }
            return $project->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'partnership_id' => $val['partnership_id'],
                    'title' => $val['title'],
                    'subtitle' => $val['subtitle'],
                    'currency' => $val['currency'],
                    'budget' => floatval($val['budget']),
                    'funds' => floatval($val['funds']),
                    'funds_needed' => floatval($val['funds_needed']),
                    'project_plan_summary' => $val['project_plan_summary'],
                    'goals_overview' => $val['goals_overview'],
                    'target_group' => $val['target_group'],
                    'background' => $val['background'],
                    'sustainability' => $val['sustainability'],
                    'current_image' => $val['current_image'],
                    'current_image_caption' => $val['current_image_caption'],
                    'status_label' => $val['status_label'],
                    'date_start_planned' => ($val['date_start_planned'] === null)
                        ? $val['date_start_planned']
                        : $this->createDateFromString($val['date_start_planned'], 'Y-m-d'),
                    'date_start_actual' => ($val['date_start_actual'] === null)
                        ? $val['date_start_actual']
                        : $this->createDateFromString($val['date_start_actual'], 'Y-m-d'),
                    'date_end_planned' => ($val['date_end_planned'] === null)
                        ? $val['date_end_planned']
                        : $this->createDateFromString($val['date_end_planned'], 'Y-m-d'),
                    'date_end_actual' => ($val['date_end_actual'] === null)
                        ? $val['date_end_actual']
                        : $this->createDateFromString($val['date_end_actual'], 'Y-m-d'),
                ],
            );
        });
        return $projects;
    }

    private function getProject($projectId)
    {
        $projects = $this->rsr->get('projects', 'id', $projectId);
        if (!is_array($projects) || !isset($projects['count']) || $projects['count'] == 0) {
            return [];
        }
        return $projects['results'][0];
    }

    public function seedRsrResults(
        RsrProject $project, RsrResult $result, RsrIndicator $indicator,
        RsrPeriod $period, RsrDimension $dimension, RsrDimensionValue $dimensionValue,
        RsrPeriodDimensionValue $periodDimensionValue, RsrPeriodData $periodData,
        Request $request
    )
    {
        $this->collections = collect();
        $this->periods = collect();
        $this->dimensions = collect();
        $this->periodData = collect();

        $results = $project->all();
        if (isset($request->total_batch) && isset($request->batch)) {
            $batch = (int) $request->batch;
            $totalBatch = (int) $request->total_batch;

            // start batch
            $count = count(\App\Partnership::all());
            $start = 1;
            $end =  intdiv($count, $totalBatch);

            // query project per batch
            if ($batch === 1) {
                $program = $results->whereNull('partnership_id')->first();
                $results = $results->whereBetween('partnership_id', [$start, $end]);
                $results->push($program);
            } else {
                $start = ($end * ($batch - 1)) + 1;
                $end = ($end * $batch);
                $end = ($totalBatch === $batch) ? $end + ($count % $totalBatch) : $end;
                $results = $results->whereBetween('partnership_id', [$start, $end]);
            }
            $results = $results->sortBy('partnership_id')->values();
        }

        $results = $results->map(function ($val) {
            return $this->getResults($val['id']);
        })->flatten(2)->reject(function ($val) {
            return count($val) === 0;
        });

        $resultTable = collect($results)->map(function ($val) use ($result) {
            if ($val['parent_result'] !== null && count($result->all()) !== 0 && $result->find($val['parent_result']) === null) {
                return [];
            }
            $this->collections->push($val['indicators']);
            $results = $result->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'rsr_project_id' => $val['project'],
                    'parent_result' => $val['parent_result'],
                    // 'title' => $val['title'],
                    // 'description' => $val['description'],
                    'order' => $val['order'],
                ]
            );
            $this->seedRsrTitleable($val, 'App\RsrResult');
            return $results;
        });

        $indicatorTable = $this->collections->flatten(1)->map(function ($val) use ($indicator) {
            $this->periods->push($val['periods']);
            $has_dimension = false;
            if (count($val['dimension_names']) > 0) {
                $has_dimension = true;
                $this->dimensions->push(
                    [
                        "indicator_id" => $val['id'],
                        "dimension_names" => $val['dimension_names'],
                        "disaggregation_targets" => $val['disaggregation_targets']
                    ]
                );
            }
            $indicators = $indicator->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'rsr_result_id' => $val['result'],
                    'parent_indicator' => $val['parent_indicator'],
                    // 'title' => $val['title'],
                    // 'description' => $val['description'],
                    'baseline_year' => $val['baseline_year'],
                    'baseline_value' => floatval($val['baseline_value']),
                    'target_value' => floatval($val['target_value']),
                    'order' => $val['order'],
                    'has_dimension' => $has_dimension,
                ],
            );
            $this->seedRsrTitleable($val, 'App\RsrIndicator');
            return $indicators;
        });

        // transform
        $dimensionTransform = $this->dimensions->transform(function ($val) {
            return collect($val['dimension_names'])->transform(function ($d) use ($val) {
                $dimensions = [
                    'rsr_dimension_id' => $d['id'],
                    'rsr_indicator_id' => $val['indicator_id'],
                    'rsr_project_id' => $d['project'],
                    'parent_dimension_name' => $d['parent_dimension_name'],
                    'name' => $d['name']
                ];
                $dimension_values = collect($d['values'])->transform(function ($v) use ($val) {
                    $find = null;
                    if (count($val['disaggregation_targets']) > 0) {
                        $find = collect($val['disaggregation_targets'])->firstWhere('dimension_value', $v['id']);
                    }
                    return [
                        'rsr_dimension_id' => $v['name'],
                        'rsr_dimension_value_id' => $v['id'],
                        'rsr_dimension_value_target_id' => $find ? $find['id'] : null,
                        'parent_dimension_value' => $v['parent_dimension_value'],
                        'name' => $v['value'],
                        'value' => $find ? (floatval($find['value'])) : 0,
                    ];
                });
                return [
                    'dimensions' => $dimensions,
                    'dimension_values' => $dimension_values,
                ];
            });
        });

        $dimensionTable = $dimensionTransform->flatten(1)->map(function ($val) use ($dimension, $dimensionValue) {
            $dimension_values = $dimension->updateOrCreate(
                [
                    'rsr_dimension_id' => $val['dimensions']['rsr_dimension_id'],
                    'rsr_indicator_id' => $val['dimensions']['rsr_indicator_id'],
                    'rsr_project_id' => $val['dimensions']['rsr_project_id'],
                ],
                Arr::except($val['dimensions'], 'name')
            );
            $val['dimensions']['id'] = $val['dimensions']['rsr_dimension_id'];
            $this->seedRsrTitleable($val['dimensions'], 'App\RsrDimension');
            $values = collect($val['dimension_values'])->map(function ($v) use ($dimensionValue, $dimension_values) {
                $v['rsr_dimension_id'] = $dimension_values->id;
                $dimensionValues = $dimensionValue->updateOrCreate(
                    [
                        'rsr_dimension_id' => $v['rsr_dimension_id'],
                        'rsr_dimension_value_id' => $v['rsr_dimension_value_id'],
                        'rsr_dimension_value_target_id' => $v['rsr_dimension_value_target_id'],
                    ],
                    Arr::except($v, 'name')
                );
                $v['id'] = $v['rsr_dimension_value_id'];
                $this->seedRsrTitleable($v, 'App\RsrDimensionValue');
                return $dimensionValues;
            });
            return $dimension_values;
        });

        $this->collections = collect();
        $periodTable = $this->periods->flatten(1)->map(function ($val) use ($period) {
            if (isset($val['disaggregations']) && count($val['disaggregations']) > 0) {
                $periodDimVal = collect($val['disaggregations'])->map(function ($ds) use ($val) {
                    $ds['period'] = $val['id'];
                    return $ds;
                });
                $this->collections->push($periodDimVal); // dimension value updated per period
                // then input this data to db on $periodDimValTable
            }
            if (count($val['data']) > 0) {
                $this->periodData->push($val['data']);
            }
            return $period->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'rsr_indicator_id' => $val['indicator'],
                    'parent_period' => $val['parent_period'],
                    'target_value' => floatval($val['target_value']),
                    'actual_value' => floatval($val['actual_value']),
                    'period_start' => ($val['period_start'] === null)
                        ? $val['period_start']
                        : $this->createDateFromString($val['period_start'], 'Y-m-d'),
                    'period_end' => ($val['period_end'] === null)
                        ? $val['period_end']
                        : $this->createDateFromString($val['period_end'], 'Y-m-d'),
                ]
            );
        });

        $periodDimValTable = $this->collections->flatten(1)->map(function ($val) use ($dimensionValue, $periodDimensionValue) {
            // $find = $dimensionValue->find($val['dimension_value']['id']);
            // if ($find === null) {
            //     return [];
            // }
            return $periodDimensionValue->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'rsr_period_id' => $val['period'],
                    'rsr_dimension_value_id' => $val['dimension_value']['id'],
                    'value' => ($val['value'] === null) ? $val['value'] : floatval($val['value']),
                ]
            );
        });

        $periodDataTable = $this->periodData->flatten(1)->map(function ($val) use ($period, $periodData) {
            if ($period->find($val['period']) === null) {
                return [];
            }
            return $periodData->updateOrCreate(
                ['id' => $val['id']],
                [
                    'id' => $val['id'],
                    'rsr_period_id' => $val['period'],
                    'value' => ($val['value'] === null) ? $val['value'] : floatval($val['value']),
                    'text' => $val['text'],
                    'created_at' => $this->createDateFromString($val['created_at'], 'Y-m-d H:i:s'),
                    'updated_at' => $this->createDateFromString($val['last_modified_at'], 'Y-m-d H:i:s')
                ]
            );
        });
        return "done";
    }

    private function seedRsrTitleable($titleData, $type)
    {
        // seed Title
        $titles = [];
        if ($type === 'App\RsrResult' || $type === 'App\RsrIndicator') {
            $titles['title'] = $titleData['title'];
            if (strlen($titleData['description']) > 0 || $titleData['description'] !== null) {
                $titles['description'] = $titleData['description'];
            }
        }
        if ($type === 'App\RsrDimension' || $type === 'App\RsrDimensionValue') {
            $titles['title'] = $titleData['name'];
        }
        $title = RsrTitle::updateOrCreate($titles, $titles);

        // seed Titleable
        $titleable = [];
        $titleable['rsr_title_id'] = $title->id;
        $titleable['rsr_titleable_id'] = $titleData['id'];
        $titleable['rsr_titleable_type'] = $type;
        return RsrTitleable::updateOrCreate($titleable, $titleable);
    }

    public function getResults($projectId, $endpoint = null)
    {
        if (!$endpoint) {
            $endpoint = 'results';
        }
        $data = collect();
        $results = $this->rsr->get($endpoint, 'project', $projectId);
        if (!is_array($results) || !isset($results['count']) || $results['count'] == 0) {
            return [];
        }
        $data->push($results['results']);
        // fetch next page
        while($results['next'] !== null){
            $results = $this->rsr->fetch($results['next']);
            if (is_array($results) && isset($results['count']) && $results['count'] !== 0) {
                $data->push($results['results']);
            }
        }
        return $data;
    }

    private function createDateFromString($date, $format)
    {
        // old way Carbon::createFromFormat('Y-m-d\TH:i:s.u', $date)->toDateTimeString()
        return Carbon::parse($date)->format($format);
    }
}
