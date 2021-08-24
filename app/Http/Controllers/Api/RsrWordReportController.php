<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Partnership;
use App\Datapoint;
use App\RsrProject;
use App\Http\Controllers\Api\ChartController;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Style\Image;

class RsrWordReportController extends Controller
{
    private $alignHCentered = array('alignment' => Jc::CENTER);
    private $alignVCentered = array('valign' => 'center');
    private $alignJustify = array('alignment' => Jc::BOTH);
    private $alignRight = array('align' => Jc::END);
    private $alignLeft = array('align' => Jc::START);
    private $transparentCellStyle = array('borderSize' => 0, 'borderColor' => '#ffffff');

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
            $pid = $request->partnership_id;
        }
        $level = $partnership->where('id', $pid)->first()->level;

        $start = false;
        $end = false;
        $reportTimeTitle = '';
        if (isset($request->year) && isset($request->selector)) {
            $year = $request->year;
            $reportTimeTitle = $request->selector.' '.$year;
            if ($request->selector === "1") {
                // get the value from last year
                $year = (int) $year - 1;
                $end = $year."-12-31";
            }
            if ($request->selector === "2" || $request->selector === "3") {
                $end = $year."-06-30";
            }
        }

        $withContribution = false;
        $rsrReport = $chart->getAndTransformRsrData($pid, $start, $end, $withContribution);
        if (count($rsrReport) === 0) {
            return response('No Data Available', 503);
        }

        // get content from flow
        $projects = [];
        $datapoints = [];
        // $projects = RsrProject::all();
        // $datapoints = Datapoint::where('form_id', $reportConfig['fid'])
        //     ->where('country_id', $pid)->with('answers')->get();

        // New Word Document
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $documentTitle = "Test";
        $phpWord = $this->renderWordDoc($documentTitle, $phpWord, $rsrReport['columns'], $rsrReport['data'], $reportTimeTitle, $country, $reportConfig, $projects, $datapoints, $level);

        // Not included the children/PPPs
        /*
        if (count($rsrReport['data']['childrens']) > 0) {
            // render document for the childrens
            foreach ($rsrReport['data']['childrens'] as $key => $child) {
                $phpWord = $this->renderWordDoc($name, $phpWord, $rsrReport['columns'], $child, $reportTimeTitle, $country, $reportConfig, $projects, $datapoints, $level);
            }
        }
        */

        // save file
        $writers = ['format' => 'Word2007', 'extension' => 'docx'];
        $name = $partnership->where('id', $pid)->first()['name'];
        $name = explode(' ', $name);
        $name = implode('_', $name);
        $filename = "Internal_Report_".implode('_', explode(' ', $reportTimeTitle))."_".$name;
        $targetFile = "{$filename}.{$writers['extension']}";
        $save = $phpWord->save($targetFile, $writers['format']);
        if (!$save) {
            return response('Failed to generate report, please try again later.', 204);
        }
        return ["link" => env('APP_URL')."/".$filename.".".$writers['extension']];
    }

    private function renderWordDoc($documentTitle, $phpWord, $columns, $data, $reportTimeTitle, $country, $reportConfig, $projects, $datapoints, $level)
    {
        $reportBody = $reportConfig['questions'];
        $n = microtime(true);

        // find partnership_id from project
        // $partnership_id = $projects->find((int) $data['rsr_project_id'])->partnership_id;
        // $answers = $datapoints->where('partnership_id', $partnership_id)->pluck('answers')->flatten(1)->values();

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

        $section->addText(htmlspecialchars('Report '.$reportTimeTitle), $titleStyle, $this->alignHCentered);
        $section->addText(htmlspecialchars($country), $titleStyle, $this->alignHCentered);
        $section->addTextBreak(2);

        $section->addText(htmlspecialchars('Partnership Name: '.$data['project']), $titleStyle);
        // $section->addLine($lineStyle);

        foreach ($reportBody as $key => $body) {
            // check if section have answers
            // $qids = collect($body['question'])->pluck('qid');
            // $checkAnswer = $answers->whereIn('question_id', $qids)->values();
            // if (count($checkAnswer) === 0 && !isset($body['table'])) {
            //     continue;
            // }

            // start rendering the section
            // Render section for PPP level
            if ($level === "partnership") {
                $numberingLevelStart = ($body['section']) === 1 ? 1 : 0;
                $section->addTextBreak(1);
                $section->addListItem($body['heading'], $numberingLevelStart, $listItemStyle, 'multilevel-'.$n);
            }

            // Start table rendering
            if (isset($body['table']) && $body['table']) {
                // $phpWord = $this->renderTable($phpWord, $section, $data, $columns);
                // config render split table
                $split = [
                    ['UII-1 BoP', 'UII-2 SHF', 'UII-3 EEP'],
                    ['UII-4 SME', 'UII-5 NonFE', 'UII-6 MSME', 'UII-7 INNO'],
                    ['UII-8 FSERV'],
                    // ["2SCALE's Contribution (€)", "Private sector contribution (in kind/in cash) (€)"],
                ];
                foreach ($split as $key => $sp) {
                    $section->addTextBreak(2);
                    $filteredColumns = $columns->whereIn('uii', $sp)->values();
                    $filteredData = $data;
                    $filteredData['columns'] = $data['columns']->whereIn('uii', $sp)->values();
                    $customWidth = false;
                    if ($key === 3) {
                        $customWidth = 3500;
                    }
                    $phpWord = $this->renderTable($phpWord, $section, $filteredData, $filteredColumns, true, $customWidth);
                }
            }
            // EOL of Table
            $section->addTextBreak(1);

            // Render section for PPP level
            if ($level === "partnership") {
                foreach ($body['question'] as $key => $question) {
                    // find answer
                    // $answer = $answers->where('question_id', $question['qid']);
                    // if(count($answer) === 0) {
                    //     continue;
                    // }
                    if ($question['numbering']) {
                        $section->addListItem($question['text'], $numberingLevelStart+1, $listItemStyle, 'multilevel-'.$n);
                    }
                    // render value
                    // foreach ($answer as $key => $item) {
                    //     $val = $item['text'] ? $item['text'] : $item['value'];
                    //     $section->addText(htmlspecialchars($val), null, $this->alignJustify);
                    // }
                    $section->addTextBreak(1);
                }
                $section->addTextBreak(1);
            }
        }
        $header = $section
            ->createHeader()
            ->addImage(url('/images/report-header.jpg'), array('width' => 80, 'align' => 'right'));
        $footer = $section->createFooter();
        $footerTable = $footer->addTable(array('borderSize' => 0));
        $footerRow = $footerTable->addRow();
        $footerCell = $footerRow->addCell(5000, $this->transparentCellStyle);
        $documentTitle = 'Internal report - '.$documentTitle.' '.$reportTimeTitle;
        $footerCell->addPreserveText($documentTitle, array('positioning' => 'absolute', 'align' => 'start'), $this->alignLeft);
        $footerCell = $footerRow->addCell(5500, $this->transparentCellStyle);
        $footerCell->addPreserveText('Page {PAGE} of {NUMPAGES}', null, $this->alignRight);
        return $phpWord;
    }

    private function renderTable($phpWord, $section, $data, $columns, $split=false, $customWidth=false)
    {
        $width = $customWidth ? $customWidth : ($split ? 850 : 350);
        $firstColumnWidth = ($split ? 850 : 350) + 10;
        $fancyTableStyle = array('borderSize' => 6, 'borderColor' => '999999', 'layout' => Table::LAYOUT_AUTO);
        $spanTableStyleName = 'Rsr Table';
        $phpWord->addTableStyle($spanTableStyleName, $fancyTableStyle);
        $table = $section->addTable($spanTableStyleName);

        // Header row
        $table->addRow();
        $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
        $table->addCell($firstColumnWidth, $cellRowSpan)->addText(htmlspecialchars('Value'), 'tableFont', $this->alignHCentered);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'first', $split, $customWidth);
        $table->addRow();
        $cellRowContinue = array('vMerge' => 'continue');
        $table->addCell($firstColumnWidth, $cellRowContinue);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'second', $split, $customWidth);
        $table->addRow();
        $table->addCell($firstColumnWidth, $cellRowContinue);
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, 'third', $split, $customWidth);
        // end of header row

        // Body
        $table->addRow();
        $table->addCell($firstColumnWidth)->addText(htmlspecialchars('Target'), 'tableFont', $this->alignHCentered);
        $data['columns']->each(function ($col) use ($table, $width) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell($width)->addText(htmlspecialchars($col['total_target_value']), 'tableFont', $this->alignHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell($width, $this->alignVCentered)->addText(htmlspecialchars($value['value']), 'tableFont', $this->alignHCentered);
                }
            }
            if (count($col['rsr_dimensions']) > 0 && count($col['rsr_indicators']) > 0) {
                foreach ($col['rsr_indicators'] as $key => $ind) {
                    $table->addCell($width, $this->alignVCentered)->addText(htmlspecialchars($ind['target_value']), 'tableFont', $this->alignHCentered);
                }
            }
        });
        $table->addRow();
        $table->addCell($firstColumnWidth)->addText(htmlspecialchars('Actual'), 'tableFont', $this->alignHCentered);
        $data['columns']->each(function ($col) use ($table, $width) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell($width)->addText(htmlspecialchars($col['total_actual_value']), 'tableFont', $this->alignHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell($width, $this->alignVCentered)->addText(htmlspecialchars($value['total_actual_value']), 'tableFont', $this->alignHCentered);
                }
            }
            if (count($col['rsr_dimensions']) > 0 && count($col['rsr_indicators']) > 0) {
                foreach ($col['rsr_indicators'] as $key => $ind) {
                    $table->addCell($width, $this->alignVCentered)->addText(htmlspecialchars($ind['total_actual_value']), 'tableFont', $this->alignHCentered);
                }
            }
        });
        // end of body
        return $phpWord;
    }

    private function renderTableHeader($phpWord, $table, $columns, $row, $split, $customWidth)
    {
        $width = $customWidth ? $customWidth : ($split ? 850 : 350);
        $columns->each(function ($col) use ($table, $row, $width) {
            // for first row
            if (count($col['subtitle']) === 0 && $row === "first") {
                $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
                $table->addCell($width, $cellRowSpan)->addText(htmlspecialchars($col['uii']), 'tableFont', $this->alignHCentered);
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
                    $table->addCell($values * $width, $cellColSpan)->addText(htmlspecialchars($col['uii']), 'tableFont', $this->alignHCentered);
                } else {
                    $cellColSpan = array('gridSpan' => $values, 'valign' => 'center');
                    $table->addCell($values * $width)->addText(htmlspecialchars($col['uii']), 'tableFont', $this->alignHCentered);
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
                            $name = "Total (€)";
                        } else {
                            $name = Str::after($name, "Newly registered ");
                        }
                        if (count($sub['values']) === 0) {
                            $cellColSpan = array('vMerge' => 'restart', 'valign' => 'center');
                            $table->addCell($valCount * $width, $cellColSpan)->addText(htmlspecialchars($name), 'tableFont', $this->alignHCentered);
                        } else {
                            $cellColSpan = array('gridSpan' => $valCount, 'valign' => 'center');
                            $table->addCell($valCount * $width, $cellColSpan)->addText(htmlspecialchars($name), 'tableFont', $this->alignHCentered);
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
                            $table->addCell($width, $this->alignVCentered)->addText(htmlspecialchars($name), 'tableFont', $this->alignHCentered);
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
