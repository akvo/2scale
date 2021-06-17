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
        $save = $phpWord->save($targetFile, $writers['format']);
        if (!$save) {
            return response('failed', 204);
        }
        return ["link" => env('APP_URL')."/".$filename.".".$writers['extension']];
    }

    private function renderWordDoc($phpWord, $columns, $data, $country)
    {
        $n = microtime(true);
        // start section
        $section = $phpWord->addSection();

        // Style
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

        $section->addText('Quarterly report month-month 2020', $titleStyle, $this->alignHCentered);
        $section->addText($country, $titleStyle, $this->alignHCentered);
        $section->addTextBreak(2);

        $section->addText('Partnership Name: '.$data['project'], $titleStyle);
        $section->addLine($lineStyle);

        // Start table rendering
        $section->addListItem('Summary of the PPPs contribution to the UIIs', 1, $listItemStyle, 'multilevel-'.$n);
        $phpWord = $this->renderTable($phpWord, $section, $data, $columns);
        // EOL of Table

        $section->addTextBreak(2);
        $section->addListItem('Incubating inclusive model', 1, $listItemStyle, 'multilevel-'.$n);
        $section->addTextBreak(1);
        $section->addListItem('Govern and adopt inclusive agribusiness partnership', 2, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Improve access to nutritional food for the BoP consumer', 2, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Foster competitiveness and inclusiveness of the food value chain', 2, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Professionalize Agribusiness Clusters', 2, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Strengthen the enabling agribusiness environment', 2, $listItemStyle, 'multilevel-'.$n);

        $section->addTextBreak(2);
        $section->addListItem('Other activities', 0, $listItemStyle, 'multilevel-'.$n);
        $section->addTextBreak(1);
        $section->addListItem('Action research', 1, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Monitoring and Evaluation', 1, $listItemStyle, 'multilevel-'.$n);
        $section->addListItem('Communications', 1, $listItemStyle, 'multilevel-'.$n);

        $section->addTextBreak(2);
        $section->addListItem('Conclusion and follow-up', 0, $listItemStyle, 'multilevel-'.$n);

        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', null, $this->alignRight);

        return $phpWord;
    }

    private function renderTable($phpWord, $section, $data, $columns)
    {
        $fancyTableStyle = array('borderSize' => 6, 'borderColor' => '999999', 'layout' => Table::LAYOUT_AUTO);
        $spanTableStyleName = 'Rsr Table';
        $phpWord->addTableStyle($spanTableStyleName, $fancyTableStyle);
        $table = $section->addTable($spanTableStyleName);

        // Header row
        $table->addRow();
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns);
        $table->addRow();
        $phpWord = $this->renderTableHeader($phpWord, $table, $columns, false);
        // end of header row

        // Body
        $table->addRow();
        $data['columns']->each(function ($col) use ($table) {
            if (count($col['rsr_dimensions']) === 0) {
                $table->addCell(1000)->addText($col['total_actual_value'], $this->alignHCentered);
            }
            if (count($col['rsr_dimensions']) > 0) {
                $dimensions = collect($col['rsr_dimensions'])->pluck('rsr_dimension_values')->flatten(1);
                foreach ($dimensions as $key => $value) {
                    $table->addCell(700, $this->alignVCentered)->addText($value['total_actual_value'], null, $this->alignHCentered);
                }
            }
        });
        // end of body
        return $phpWord;
    }

    private function renderTableHeader($phpWord, $table, $columns, $firstRow=true)
    {
        $columns->each(function ($col) use ($table, $firstRow) {
            // for first row
            if (count($col['subtitle']) === 0 && $firstRow) {
                $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
                $table->addCell(350, $cellRowSpan)->addText($col['uii'], $this->alignHCentered);
            }
            if (count($col['subtitle']) > 0 && $firstRow) {
                $subtitles = collect($col['subtitle'])->pluck('values')->flatten(1);
                $cellColSpan = array('gridSpan' => count($subtitles), 'valign' => 'center');
                $table->addCell(count($subtitles) * 350, $cellColSpan)->addText($col['uii'], null, $this->alignHCentered);
            }
            // for second row
            if (count($col['subtitle']) === 0 && !$firstRow) {
                $cellRowContinue = array('vMerge' => 'continue');
                $table->addCell(350, $cellRowContinue);
            }
            if (count($col['subtitle']) > 0 && !$firstRow) {
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
                    $table->addCell(350, $this->alignVCentered)->addText($name, null, $this->alignHCentered);
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
