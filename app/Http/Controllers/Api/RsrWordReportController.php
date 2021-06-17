<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Partnership;
use App\Http\Controllers\Api\ChartController;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table;

class RsrWordReportController extends Controller
{
    private $alignHCentered = array('alignment' => Jc::CENTER);
    private $alignVCentered = array('valign' => 'center');
    private $alignJustify = array('alignment' => Jc::BOTH);
    private $alignRight = array('align' => Jc::END);

    public function getRsrWordReport(Request $request)
    {
        $reportConfig = config('rsr-word-report');
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

        $start = false;
        $end = false;
        if (isset($request->start) && isset($request->end)) {
            $start = $request->start;
            $end = $request->end;
        }

        $rsrReport = $chart->getAndTransformRsrData($pid, $start, $end);
        if (count($rsrReport) === 0) {
            return response('no data available', 503);
        }
        // return $rsrReport;

        // New Word Document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord = $this->renderWordDoc($phpWord, $rsrReport['columns'], $rsrReport['data'], $country, $reportConfig['questions']);

        if (count($rsrReport['data']['childrens']) > 0) {
            // render document for the childrens
            foreach ($rsrReport['data']['childrens'] as $key => $child) {
                $phpWord = $this->renderWordDoc($phpWord, $rsrReport['columns'], $child, $country, $reportConfig['questions']);
            }
        }

        // save file
        $writers = ['format' => 'Word2007', 'extension' => 'docx'];
        $filename = "RSR_Report_".$country;
        $targetFile = "{$filename}.{$writers['extension']}";
        $save = $phpWord->save($targetFile, $writers['format']);
        if (!$save) {
            return response('failed', 204);
        }
        return ["link" => env('APP_URL')."/".$filename.".".$writers['extension']];
    }

    private function renderWordDoc($phpWord, $columns, $data, $country, $reportBody)
    {
        $n = microtime(true);
        // Style
        $phpWord->addFontStyle('tableFont', array('size' => 8));
        $listFormat = $phpWord->addNumberingStyle(
            'multilevel-'.$n,
            array('type' => 'multilevel', 'levels' => array(
                    array('format' => 'decimal', 'text' => '%1.', 'left' => 360, 'hanging' => 360, 'tabPos' => 50),
                    array('format' => 'decimal', 'text' => '%1.%2', 'left' => 360, 'hanging' => 360, 'tabPos' => 100),
                    array('format' => 'decimal', 'text' => '%1.%2.%3', 'left' => 360, 'hanging' => 360, 'tabPos' => 150),
                )
            )
        );
        $titleStyle = array('size' => 12, 'bold' => true);
        $listItemStyle = array('bold' => true);
        $lineStyle = array('weight' => 1, 'width' => 430, 'height' => 0);
        // EOL Style

        // start section
        $section = $phpWord->addSection();
        $sectionStyle = $section->getStyle();
        $sectionStyle->setMarginRight(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5));
        $sectionStyle->setMarginLeft(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(1.5));

        $section->addText('Quarterly report month-month 2020', $titleStyle, $this->alignHCentered);
        $section->addText($country, $titleStyle, $this->alignHCentered);
        $section->addTextBreak(2);

        $section->addText('Partnership Name: '.$data['project'], $titleStyle);
        $section->addLine($lineStyle);

        $lipsum = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras varius molestie ipsum. Vestibulum maximus eget elit id mattis. Aliquam quis felis ac neque imperdiet maximus. Vestibulum molestie nibh mauris, at vehicula neque fringilla sit amet. Praesent vel posuere lectus. Sed sagittis magna est, condimentum suscipit purus facilisis et. Vestibulum ante.";
        foreach ($reportBody as $key => $body) {
            $numberingLevelStart = ($body['section']) === 1 ? 1 : 0;
            $section->addTextBreak(1);
            $section->addListItem($body['heading'], $numberingLevelStart, $listItemStyle, 'multilevel-'.$n);

            // Start table rendering
            if (isset($body['table']) && $body['table']) {
                // $phpWord = $this->renderTable($phpWord, $section, $data, $columns);
                // config render split table
                $split = [
                    ['UII-1 BoP', 'UII-2 SHF', 'UII-3 EEP'],
                    ['UII-4 SME', 'UII-5 NonFE', 'UII-6 MSME', 'UII-7 INNO'],
                    ['UII-8 FSERV'],
                ];
                foreach ($split as $key => $sp) {
                    $section->addTextBreak(2);
                    $filteredColumns = $columns->whereIn('uii', $sp)->values();
                    $filteredData = $data;
                    $filteredData['columns'] = $data['columns']->whereIn('uii', $sp)->values();
                    $phpWord = $this->renderTable($phpWord, $section, $filteredData, $filteredColumns, true);
                }
            }
            // EOL of Table
            $section->addTextBreak(1);

            foreach ($body['question'] as $key => $question) {
                if ($question['numbering']) {
                    $section->addListItem($question['text'], $numberingLevelStart+1, $listItemStyle, 'multilevel-'.$n);
                    // render value
                    $section->addText($lipsum, null, $this->alignJustify);
                } else {
                    // render value
                    $section->addText($lipsum, null, $this->alignJustify);
                }
                $section->addTextBreak(1);
            }
            $section->addTextBreak(1);
        }

        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', null, $this->alignRight);

        return $phpWord;
    }

    private function renderTable($phpWord, $section, $data, $columns, $split=false)
    {
        $width = $split ? 850 : 350;
        $firstColumnWidth = $width + 10;
        $fancyTableStyle = array('borderSize' => 6, 'borderColor' => '999999', 'layout' => Table::LAYOUT_AUTO);
        $spanTableStyleName = 'Rsr Table';
        $phpWord->addTableStyle($spanTableStyleName, $fancyTableStyle);
        $table = $section->addTable($spanTableStyleName);

        // Header row
        $table->addRow();
        $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
        $table->addCell($firstColumnWidth, $cellRowSpan)->addText('Value', 'tableFont', $this->alignHCentered);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'first', $split);
        $table->addRow();
        $cellRowContinue = array('vMerge' => 'continue');
        $table->addCell($firstColumnWidth, $cellRowContinue);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'second', $split);
        $table->addRow();
        $table->addCell($firstColumnWidth, $cellRowContinue);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'third', $split);
        // end of header row

        // Body
        $table->addRow();
        $table->addCell($firstColumnWidth)->addText('Target', 'tableFont', $this->alignHCentered);
        $data['columns']->each(function ($col) use ($table, $width) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell($width)->addText($col['total_target_value'], 'tableFont', $this->alignHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell($width, $this->alignVCentered)->addText($value['value'], 'tableFont', $this->alignHCentered);
                }
            }
            if (count($col['rsr_dimensions']) > 0 && count($col['rsr_indicators']) > 0) {
                foreach ($col['rsr_indicators'] as $key => $ind) {
                    $table->addCell($width, $this->alignVCentered)->addText($ind['target_value'], 'tableFont', $this->alignHCentered);
                }
            }
        });
        $table->addRow();
        $table->addCell($firstColumnWidth)->addText('Actual', 'tableFont', $this->alignHCentered);
        $data['columns']->each(function ($col) use ($table, $width) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell($width)->addText($col['total_actual_value'], 'tableFont', $this->alignHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell($width, $this->alignVCentered)->addText($value['total_actual_value'], 'tableFont', $this->alignHCentered);
                }
            }
            if (count($col['rsr_dimensions']) > 0 && count($col['rsr_indicators']) > 0) {
                foreach ($col['rsr_indicators'] as $key => $ind) {
                    $table->addCell($width, $this->alignVCentered)->addText($ind['total_actual_value'], 'tableFont', $this->alignHCentered);
                }
            }
        });
        // end of body
        return $phpWord;
    }

    private function renderTableHeader($phpWord, $table, $columns, $row, $split)
    {
        $width = $split ? 850 : 350;
        $columns->each(function ($col) use ($table, $row, $width) {
            // for first row
            if (count($col['subtitle']) === 0 && $row === "first") {
                $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
                $table->addCell($width, $cellRowSpan)->addText($col['uii'], 'tableFont', $this->alignHCentered);
            }
            if (count($col['subtitle']) > 0 && $row === "first") {
                $values = collect($col['subtitle'])->map(function ($s) {
                    if (count($s['values']) === 0) {
                        return 1;
                    }
                    return count($s['values']);
                })->sum();
                $subCount = count($col['subtitle']);
                if ($subCount === 1) {
                    $cellColSpan = array('gridSpan' => $values, 'vMerge' => 'restart', 'valign' => 'center');
                    $table->addCell($values * $width, $cellColSpan)->addText($col['uii'], 'tableFont', $this->alignHCentered);
                } else {
                    $cellColSpan = array('gridSpan' => $values, 'valign' => 'center');
                    $table->addCell($values * $width)->addText($col['uii'], 'tableFont', $this->alignHCentered);
                }
            }
            // for second row
            if (count($col['subtitle']) === 0 && $row === "second") {
                $cellRowContinue = array('vMerge' => 'continue');
                $table->addCell($width, $cellRowContinue);
            }
            if (count($col['subtitle']) > 0 && $row === "second") {
                $values = collect($col['subtitle'])->map(function ($s) {
                    if (count($s['values']) === 0) {
                        return 1;
                    }
                    return count($s['values']);
                })->sum();
                $subCount = count($col['subtitle']);
                if ($subCount === 1) {
                    $cellRowContinue = array('vMerge' => 'continue', 'gridSpan' => $values, 'valign' => 'center');
                    $table->addCell($width, $cellRowContinue);
                } else {
                    foreach ($col['subtitle'] as $key => $sub) {
                        $valCount = count($sub['values']);
                        $name = $sub['name'];
                        if (Str::contains($name, "Total")) {
                            $name = "Total";
                        } else {
                            $name = Str::after($name, "Newly registered ");
                        }
                        if (count($sub['values']) === 0) {
                            $cellColSpan = array('vMerge' => 'restart', 'valign' => 'center');
                            $table->addCell($valCount * $width, $cellColSpan)->addText($name, 'tableFont', $this->alignHCentered);
                        } else {
                            $cellColSpan = array('gridSpan' => $valCount, 'valign' => 'center');
                            $table->addCell($valCount * $width, $cellColSpan)->addText($name, 'tableFont', $this->alignHCentered);
                        }
                    }
                }
            }
            // for third row
            if (count($col['subtitle']) === 0 && $row === "third") {
                $cellRowContinue = array('vMerge' => 'continue');
                $table->addCell($width, $cellRowContinue);
            }
            if (count($col['subtitle']) > 0 && $row === "third") {
                foreach ($col['subtitle'] as $key => $sub) {
                    if (count($sub['values']) === 0) {
                        $cellRowContinue = array('vMerge' => 'continue', 'valign' => 'center');
                        $table->addCell($width, $cellRowContinue);
                    } else {
                        foreach ($sub['values'] as $key => $value) {
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
                            $table->addCell($width, $this->alignVCentered)->addText($name, 'tableFont', $this->alignHCentered);
                        }
                    }
                }
            }
        });
        return $phpWord;
    }

    private function getPartnershipCache()
    {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }
        return $partnership;
    }
}
