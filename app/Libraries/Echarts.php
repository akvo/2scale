<?php
namespace App\Libraries;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class Echarts
{
    public function __construct(){
        // $this->pallete = array(
        //     '#ff4444','#ffbb33', '#00C851', '#33b5e5', '#2BBBAD','#4285F4',  '#aa66cc', '#ff7043','#b2dfdb', '#b3e5fc', '#8d6e63','#f78bba','#231fa1'
        // );
        $this->pallete = array("#a43332", "#609ba7", "#C9CDC0", "#3D588A", "#5C616A", "#DBC2CF", "#2E4756", "#CC9485", "#CCD3B6", "#FFE800");
    }
    private function generateLegend($legend, $textStyle, $orient = 'horizontal', $x = 'center', $y = 'top') {
        return array(
            'orient' => $orient,
            'x' => $x,
            'y' => $y,
            'textStyle' => $textStyle,
            'data' => $legend,
            'icon' => 'circle',
        );
    }
    public function generateDonutCharts($legend, $data, $titleShow = false){
        $legend = collect($legend)->map(function($l){
            return $this->titler($l);
        });
        $data = collect($data)->map(function($d){
            $d['name'] = $this->titler($d['name']);
            return $d;
        });
        $legendStyle = array(
            'fontFamily' => 'MarkPro',
            'fontWeight' => 200,
            'fontSize' => 14,
        );
        $total = $data->sum('value');
        $left = ($total >= 10000)
                    ? "46%"
                    : (($total >= 1000) ? "47%" : "48%");
        return array (
          'title' => [
              'show' => $titleShow,
              'text' => $total,
              'top' => '47%',
              'left' => $left,
          ],
          'color' => $this->pallete,
          'tooltip' => array ( 'trigger' => 'item'),
          'toolbox' => array (
            'show' => true,
            'right' => 'right',
            'bottom' => 'bottom',
            'feature' => array (
                'saveAsImage' => array(
                    'title' => 'Save Image',
                ),
            ),
          ),
          'legend' => $this->generateLegend($legend, $legendStyle, 'horizontal', 'center', 'bottom'),
          'series' => array (
            array (
              'type' => 'pie',
              'radius' => array ( '40%', '70%'),
              'avoidLabelOverlap' => false,
              'label' =>
              array (
                'normal' =>
                array (
                    'show' => false,
                    'position' => 'inside'
                ),
                'emphasis' =>
                array (
                  'show' => true,
                  'textStyle' =>
                  array (
                    'fontSize' => '20',
                    'fontWeight' => 'bold',
                  ),
                ),
              ),
              'labelLine' =>
              array (
                'normal' =>
                array (
                  'show' => false,
                ),
              ),
              'data' => $data
            ),
          ),
        );
    }

    public function generateSimpleBarCharts($categories, $values, $showLabel=false, $yAxisLabel=false)
	{
        $categories = collect($categories)->map(function($l) {
            return $this->titler($l);
        });
		$option = [
            'dataZoom' => array(
                'type' => 'inside',
                'yAxisIndex' => [0]
            ),
			'tooltip' => array (
				'trigger' => 'axis',
				'axisPointer' => array ('type' => 'shadow'),
			),
			"grid" => array (
				"left" => "50%",
				"top" => "0px",
				"bottom" => "0px",
            ),
            "label" => [
                "show" => $showLabel,
                "position" => "inside"
            ],
			"yAxis" => [
				"type" => "category",
				"data" => $categories,
				"axisTick" => [
					"alignWithLabel" => True,
					"inside" => True
                ],
			],
			"xAxis" => [
				"type" => "value",
			],
			"series" => [[
            	'color' => $this->pallete,
				"data" => $values,
				"type" => "bar"
			]]
        ];
        if($yAxisLabel) {
            $option["yAxis"]["axisLabel"] = [
                "fontSize" => 16,
            ];
        }
        return $option;
    }

    public function generateBarCharts($legend, $categories, $type, $series, $xMax = false, $dataset = [])
    {
        $legend = collect($legend)->map(function($l) {
            return $this->titler($l);
        });
        $categories = collect($categories)->map(function($l) {
            return $this->titler($l);
        });
        $series = collect($series)->map(function($l) {
            $l['name'] = $this->titler($l['name']);
            return $l;
        });
        $textStyle = array(
            'fontFamily' => 'MarkPro',
            'fontWeight' => 200
        );
        $legendStyle = array(
            'fontFamily' => 'MarkPro',
            'fontWeight' => 200,
            'fontSize' => 14,
        );
        $labels = array(
            'normal' => array (
                'show' => true,
                'position' => 'inside',
            )
        );
        if (!$xMax) { // if series not custom stack bar
            $series = collect($series)->map(function($data) use ($labels) {
                $data['label'] = $labels;
                $data['type'] = 'bar';
                return $data;
            });
        }
        $yAxis = array(
            'type' => 'value',
            'axisLabel' => $textStyle
        );
        $xAxis = array(
            'type' => 'category',
            'data' => $categories,
            'axisLabel' => $textStyle,
            'axisTick' => array( 'show' => false)
        );
        if ($type === 'Horizontal') {
            $yAxis = array(
                'type' => 'category',
                'data' => $categories,
                'axisLabel' => $textStyle,
                'axisTick' => array( 'show' => false)
            );
            if (count($dataset) > 0) {
                $yAxis = array(
                    'type' => 'category',
                    'axisLabel' => $textStyle,
                    'axisTick' => array( 'show' => false)
                );
            }
            $xAxis = array(
                'type' => 'value',
                'axisLabel' => $textStyle
            );
            // if ($xMax) {
            //     $xAxis['max'] = $xMax;
            // }
        }
        $tooltip = array (
            'trigger' => 'axis',
            'axisPointer' => array ('type' => 'shadow'),
        );
        $option = [
            'color' => ($xMax) ? ['#609CA7', '#a43332'] : $this->pallete,
            // 'dataZoom' => array(
            //     'type' => 'inside',
            //     'yAxisIndex' => [0]
            // ),
			'tooltip' => $tooltip,
			'grid' => array(
				'left' => '3%',
				'bottom' => '30.5%',
				'right' => '4%',
				'top' => '3%',
				'containLabel' => true
			),
            'legend' => $this->generateLegend($legend, $legendStyle, 'horizontal', 'center', 'bottom'),
            'toolbox' => array(
                'show' => true,
                'feature' => array(
                    'saveAsImage' => array( 'show' =>  true, 'title' => 'Save')
                ),
                'bottom' => 0,
                'right' => 0,
            ),
            'series' => $series,
            'xAxis' => $xAxis,
            'yAxis' => $yAxis
        ];
        if (count($dataset) > 0) { // if series custom stack bar
            // dataset source
            $option['dataset'] = [
                'source' => $dataset
            ];
        }
        return $option;
    }
    public function generateMapCharts($data, $min, $max){
        $textStyle = array(
            'fontFamily' => 'MarkPro',
            'fontWeight' => 500,
            'fontStyle' => 'italic',
        );
        $data = collect($data)->map(function($dt){
            $dt = collect($dt);
            $dt['emphasis'] = array(
                'label' => array(
                    'show' => true,
                    'fontSize' => 14,
                	'fontWeight' => 400,
					'color' => '#fff'
                ),
            );
            $dt['label'] = array(
                'show' => false,
                'fontSize' => 12,
				'color' => '#fff'
            );
			$dt['itemStyle'] = array(
                'normal' => array(
                    'color' => $this->pallete[0],
                    'borderWidth' => 2,
                    'borderColor' => "#fff"
                ),
				'emphasis' => array(
                    'shadowOffsetX' =>  0,
                    'shadowOffsetY' => 0,
                    'shadowBlur' => 50,
                    'borderWidth' => 2,
                    'borderColor' => '#FFF',
                    'shadowColor' => 'rgba(0, 0, 0, .7)',
                    'color' => $this->pallete[0]
				)
			);
            return $dt;
        })->toArray();
        $steps = array (
            'right' => 'center',
            'top' => 'bottom',
            'orient' => 'horizontal',
            'splitList' => array (
                    array (
                      'start' => 1000,
                      'label' => '> 1000',
                    ),
                    array (
                      'start' => 100,
                      'end' => 1000,
                    ),
                    array (
                       'label' => '100 <',
                       'start' => 0,
                       'end' => 100,
                    ),
            ),
            'textStyle' => array (
                'fontFamily' => 'MarkPro',
                'fontWeight' => 600,
                'fontSize' => 12
            ),
            'color' => array("#a43332", "#609ba7", "#C9CDC0")
        );
        return array (
          'tooltip' => array (
            'trigger' => 'item',
            'showDelay' => 0,
            'transitionDuration' => 0.2,
            'formatter' => '{a}',
          ),
          'series' => array(
            array(
              'type' => 'map',
              'zoom' => 1,
              'room' => true,
              'aspectScale' => 1,
              'map' => 'africa',
              'data' => $data,
            ),
          ),
          'textStyle' => $textStyle,
        );
    }

    private function titler($name) {
        return ucwords(str_replace('_',' ', strtolower($name)));
    }

    public function generateBarLineCharts()
	{
		return [
			'tooltip' => array (
				'trigger' => 'axis',
				'axisPointer' => array ('type' => 'shadow'),
			),
            "legend" => array (
                "data" => ["Target", "Actual"]
            ),
			"yAxis" => [
                array(
                    "type" => "value",
                    "min" => 0,
                    "max" => 250,
                    "interval" => 50,
                    "axisLabel" => [
                        "formatter" => "USD {value}"
                    ]
                ),
                array(
                    "type" => "value",
                    "min" => 0,
                    "max" => 250,
                    "interval" => 50,
                    "axisLabel" => [
                        "formatter" => "USD {value}"
                    ],
                    "show" => false,
                )
			],
			"xAxis" => [
                "type" => "category",
                "data" => ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                "axisPointer" => ["type" => "shadow"]
			],
			"series" => [
                [
                    "name" => "Target",
                    "type" => 'line',
                    "yAxisIndex" => 1,
                    "data" => [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3]
                ],
                [
                    "name" => 'Actual',
                    "type" => 'bar',
                    "data" => [2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 155.6, 152.2, 48.7, 18.8, 6.0, 2.3]
                ],
            ]
		];
    }

    public function generateTreeMapCharts($name, $data)
    {
        $levelOption = [
            [
                'itemStyle' => [
                    'borderColor' => '#777',
                    'borderWidth' => 0,
                    'gapWidth' => 1
                ],
                'upperLabel' => [
                    'show' => false
                ]
            ],
            [
                'itemStyle' => [
                    'borderColor' => '#555',
                    'borderWidth' => 5,
                    'gapWidth' => 1
                ],
                'emphasis' => [
                    'itemStyle' => [
                        'borderColor' => '#ddd'
                    ]
                ]
            ],
            [
                'colorSaturation' => [0.35, 0.5],
                'itemStyle' => [
                    'borderWidth' => 5,
                    'gapWidth' => 1,
                    'borderColorSaturation' => 0.6
                ]
            ]
        ];

        return [
            'toolbox' => array(
                'show' => true,
                'feature' => array(
                    'saveAsImage' => array( 'show' =>  true, 'title' => 'Save')
                ),
                'bottom' => 0,
                'right' => 0,
            ),
            'series' => [[
                'color' => $this->pallete,
                'name' => $name,
                'type' => 'treemap',
                'visibleMin' => 300,
                'label' => [
                    'show' => true,
                    'formatter' => '({c}) - {b}',
                ],
                'upperLabel' => [
                    'show' => true,
                    'height' => 30,
                    'formatter' => '({c}) - {b}',
                ],
                'itemStyle' => [
                    'borderColor' => '#fff',
                ],
                'levels' => $levelOption,
                'data' => $data
            ],
            ],
        ];
    }
}
