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
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\TablePosition;
use App\Http\Controllers\Api\ChartController;

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

    public function getRsrWordReport(Request $request)
    {
        $chart = new ChartController();
        $partnership = $this->getPartnershipCache();
        $pid = null;
        $country = "";
        if (isset($request->country_id) && $request->country_id !== "0") {
            $pid = $request->country_id;
            $country = $partnership->where('id', $request->country_id)->first()->name;
        }
        if (isset($request->partnership_id) && $request->partnership_id !== "0") {
            $pid = $request->country_id;
            // $pid = $request->partnership_id;
        }
        $rsrReport = $chart->getAndTransformRsrData($pid);
        if (count($rsrReport) === 0) {
            return response('no data available', 503);
        }

        // New Word Document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord = $this->renderWordDoc($phpWord, $rsrReport['columns'], $rsrReport['data'], $country);

        if (count($rsrReport['data']['childrens']) > 0) {
            // render document for the childrens
            foreach ($rsrReport['data']['childrens'] as $key => $child) {
                $phpWord = $this->renderWordDoc($phpWord, $rsrReport['columns'], $child, $country);
            }
        }

        // save file
        $writers = ['format' => 'Word2007', 'extension' => 'docx'];
        $filename = "test";
        $targetFile = "{$filename}.{$writers['extension']}";
        return (string) $phpWord->save($targetFile, $writers['format']);
    }

    private function renderWordDoc($phpWord, $columns, $data, $country)
    {
        // Style
        $phpWord->addNumberingStyle(
            'hNum',
            array('type' => 'multilevel', 'levels' => array(
                array('pStyle' => 'Heading1', 'format' => 'decimal', 'text' => '%1'),
                array('pStyle' => 'Heading2', 'format' => 'decimal', 'text' => '%1.%2'),
                array('pStyle' => 'Heading3', 'format' => 'decimal', 'text' => '%1.%2.%3'),
                )
            )
        );
        $phpWord->addTitleStyle(1, array('size' => 10, 'bold' => true), array('numStyle' => 'hNum', 'numLevel' => 0));
        $phpWord->addTitleStyle(2, array('size' => 10, 'bold' => true), array('numStyle' => 'hNum', 'numLevel' => 1));
        $phpWord->addTitleStyle(3, array('size' => 10, 'bold' => true), array('numStyle' => 'hNum', 'numLevel' => 2));
        // $section->addTitle('Heading 1', 1);
        // $section->addTitle('Heading 2', 2);
        // $section->addTitle('Heading 3', 3);

        $justify = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH);
        $cellHCentered = array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER);
        $cellVCentered = array('valign' => 'center');
        $fancyTableStyle = array('borderSize' => 6, 'borderColor' => '999999');
        $lineStyle = array('weight' => 1, 'width' => 430, 'height' => 0);
        // EOL Style

        $section = $phpWord->addSection();
        $section->addText('Quarterly report month-month 2020', array('size' => 12, 'bold' => true), $cellHCentered);
        $section->addText($country, array('size' => 12, 'bold' => true), $cellHCentered);
        $section->addTextBreak(2);

        $section->addText('Partnership Name: '.$data['project'], array('size' => 12, 'bold' => true));
        $section->addLine($lineStyle);

        // Start table rendering
        $section->addTitle('Summary of the PPPs contribution to the UIIs', 2);
        $spanTableStyleName = 'Rsr Table';
        $phpWord->addTableStyle($spanTableStyleName, $fancyTableStyle);
        $table = $section->addTable($spanTableStyleName);

        // Header row
        $table->addRow();
        $columns->each(function ($col) use ($table, $cellHCentered, $cellVCentered) {
            if (count($col['subtitle']) === 0) {
                $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
                $table->addCell(350, $cellRowSpan)->addText($col['uii'], $cellHCentered);
            }
            if (count($col['subtitle']) > 0) {
                $subtitles = collect($col['subtitle'])->pluck('values')->flatten(1);
                $cellColSpan = array('gridSpan' => count($subtitles), 'valign' => 'center');
                $table->addCell(count($subtitles) * 350, $cellColSpan)->addText($col['uii'], null, $cellHCentered);
            }
        });

        $table->addRow();
        $columns->each(function ($col) use ($table, $cellHCentered, $cellVCentered) {
            if (count($col['subtitle']) === 0) {
                $cellRowContinue = array('vMerge' => 'continue');
                $table->addCell(350, $cellRowContinue);
            }
            if (count($col['subtitle']) > 0) {
                $subtitles = collect($col['subtitle'])->pluck('values')->flatten(1)->values();
                foreach ($subtitles as $key => $value) {
                    $name = $value;
                    if (!Str::contains($name, ">") && !Str::contains($name, "<")) {
                        if (Str::contains($name, "Male")) {
                            $name = "M";
                        }
                        if (Str::contains($name, "Female")) {
                            $name = "F";
                        }
                    }
                    if (Str::contains($name, ">") || Str::contains($name, "<")) {
                        if (Str::contains($name, "Male") && Str::contains($name, ">")) {
                            $name = "SM";
                        }
                        if (Str::contains($name, "Male") && Str::contains($name, "<")) {
                            $name = "JM";
                        }
                        if (Str::contains($name, "Female") && Str::contains($name, ">")) {
                            $name = "SF";
                        }
                        if (Str::contains($name, "Female") && Str::contains($name, "<")) {
                            $name = "JF";
                        }
                    }
                    $table->addCell(350, $cellVCentered)->addText($name, null, $cellHCentered);
                }
            }
        });
        // end of header row

        // Body
        $table->addRow();
        $data['columns']->each(function ($col) use ($table, $cellHCentered, $cellVCentered) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell(1000)->addText($col['total_actual_value'], $cellHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell(700, $cellVCentered)->addText($value['total_actual_value'], null, $cellHCentered);
                }
            }
        });
        // end of body
        // EOL of Table
        $lorem = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut ut feugiat tortor. Nullam risus felis, ultrices et feugiat a, elementum a odio. Nam tempor, sapien sit amet iaculis commodo, nibh diam venenatis dolor, et semper ante lorem et nibh. Praesent vitae velit sed eros sollicitudin accumsan sit amet eget magna. Ut malesuada ante eu arcu sollicitudin fermentum. Nam vulputate, lectus tempor accumsan placerat, velit libero posuere purus, sed tempus enim nisi ac lorem. Donec ornare justo elit, a vestibulum ipsum consequat id.';

        $section->addTextBreak(2);
        $section->addTitle('Incubating inclusive model', 2);
        $section->addText($lorem, null, $justify);
        $section->addTextBreak(1);
        $section->addTitle('Govern and adopt inclusive agribusiness partnership', 3);
        $section->addTitle('Improve access to nutritional food for the BoP consumer', 3);
        $section->addTitle('Foster competitiveness and inclusiveness of the food value chain ', 3);
        $section->addTitle('Professionalize Agribusiness Clusters', 3);
        $section->addTitle('Strengthen the enabling agribusiness environment', 3);
        return $phpWord;
    }

}
