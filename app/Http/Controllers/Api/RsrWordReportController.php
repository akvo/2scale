<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Partnership;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\TablePosition;
use App\Http\Controllers\Api\ChartController;

class RsrWordReportController extends Controller
{
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
        // Style
        $phpWord->addNumberingStyle(
            'hNum',
            array('type' => 'multilevel', 'levels' => array(
                    array('pStyle' => 'Heading1', 'format' => 'decimal', 'text' => '%1', 'start' => 1),
                    array('pStyle' => 'Heading2', 'format' => 'decimal', 'text' => '%1.%2', 'start' => 1),
                    array('pStyle' => 'Heading3', 'format' => 'decimal', 'text' => '%1.%2.%3', 'start' => 1),
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
        $fancyTableStyle = array('borderSize' => 6, 'borderColor' => '999999', 'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_AUTO);
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

        $footer = $section->addFooter();
        $footer->addPreserveText('Page {PAGE} of {NUMPAGES}', null, array('align' => \PhpOffice\PhpWord\SimpleType\Jc::END));

        return $phpWord;
    }

    private function getPartnershipCache() {
        $partnership = Cache::get('partnership');
        if (!$partnership) {
            $partnership = Partnership::all();
            Cache::put('partnership', $partnership, 86400);
        }
        return $partnership;
    }
}
