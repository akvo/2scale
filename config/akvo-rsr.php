<?php

$ROOT_API = env('RSR_API_ROOT', '');

return [
    'token' => env('RSR_TOKEN', ''),
    'endpoints' => [
        'projects' => $ROOT_API . '/project', # /param
        'updates' => $ROOT_API . '/project_update', # /param
        'results' => $ROOT_API . '/results_framework', # /param
        'partnership' => $ROOT_API . '/partnership', #param
        'rsr_page' => 'https://rsr.akvo.org/en/project/', # project_id
    ],
    'home_charts' => [
        # using program rsr result id
        'investment_tracking' => [
            'amount_of_co_financing' => [
                'id' => 48191,
                'name' => "Private sector contributions (in cash/in kind) €",
            ], # title_id 148, # title id based on result, indicator title id 157 # parent result of this value 48191
            '2scale_contribution' => [
                'id' => 48259,
                'name' => "2SCALE's contributions (in cash/in kind) €",
            ], # title_id 149, # title id based on result, indicator title id 158 # parent result of this value 48259
        ],
    ],
    'impact_react_charts' => [
        # using program rsr result id
        # MAX is aggregation type, if false aggregation is SUM (default)
        [
            'id' => 42401,
            'name' => 'UII1 - BoP',
            'result_title' => 'UII-1 BoP: BoP consumers have improved access to affordable food products',
            'group' => 'Food and Nutrition Security',
            'target_text' => '##number## BoP consumers with improved access to affordable food products.',
            'tab' => [
                'text' => 'BoP consumers with improved access to nutritious foods',
                'bold' => 'BoP consumers '
            ],
            'max' => true,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42865,
            'name' => 'UII2 - SHF',
            'result_title' => 'UII-2 SHF: Smallholder farmers have improved agricultural productivity levels and have better terms of inclusion (value chains/ markets).',
            'group' => 'Food and Nutrition Security',
            'tab' => [
                'text' => 'Smallholder farmers with improved agricultural productivity and access to markets. (50% women, 40% youth)',
                'bold' => 'Smallholder farmers '
            ],
            'target_text' => '##number## smallholder farmers (50% women, 40% youth) with improved agricultural productivity and access to markets.',
            'max' => true,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42698,
            'name' => 'UII3 - EEP',
            'result_title' => 'UII-3 EEP: Rural communities are resilient to the implications of climate change.',
            'group' => 'Food and Nutrition Security',
            'tab' => [
                'text' => 'Eco-efficient farming practices adopted in a new area.',
                'bold' => 'Eco-efficient farming practices '
            ],
            'target_text' => 'Eco-efficient farming practices adopted in a new area of ##number## hectares.',
            'max' => true,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42804,
            'name' => 'UII4 - SME',
            'result_title' => 'UII-4 SME: SMEs drive inclusive business in target industries and develop leadership in industry platforms/ networks and policy advocacy',
            'group' => 'Inclusive Agribusiness and Innovation',
            'tab' => [
                'text' => 'SMEs driving inclusive business in target industries (50% women-led/women-owned)',
                'bold' => 'SMEs '
            ],
            'target_text' => '##number## SMEs ­driving inclusive business in target industries (125 women-led/women-owned).',
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42825,
            'name' => 'UII5 - NONFE',
            'result_title' => 'UII-5 NonFE: Creation of remunerative additional non-farming employment in targeted agribusiness clusters and value chains.',
            'group' => 'Inclusive Agribusiness and Innovation',
            'tab' => [
                'text' => 'Non-farming jobs in targeted agribusiness clusters and value chains.(50% women, 40% youth)',
                'bold' => 'Non-farming jobs '
            ],
            'target_text' => '##number## non-farming jobs in targeted agribusiness clusters and value chains (10,000 women; 8,000 youth).',
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42835,
            'name' => 'UII6 - MSME',
            'result_title' => 'UII-6 MSME: Development of economically attractive and viable opportunities for micro-entrepreneurs and SMEs affiliated with the inclusive agribusiness',
            'group' => 'Inclusive Agribusiness and Innovation',
            'tab' => [
                'text' => 'Micro entrepreneurs/SMEs associated with partnerships. (50% women-led/women-owned and 40% young entrepreneurs)',
                'bold' => 'Micro entrepreneurs/SMEs '
            ],
            'target_text' => '##number## micro entrepreneurs/SMEs (2,500 women-led/women-owned, 1,000 young entrepreneurs) associated with partnerships.',
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42845,
            'name' => 'UII7 - INNO',
            'result_title' => 'UII-7 INNO: Innovative capacity of SMEs, micro-entrepreneurs, farmers strengthened',
            'group' => 'Inclusive Agribusiness and Innovation',
            'tab' => [
                'text' => 'Non-farming innovations adopted.',
                'bold' => 'Non-farming innovations '
            ],
            'target_text' => '##number## non-farming innovations adopted.',
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 42855,
            'name' => 'UII8 - FSERV',
            'result_title' => 'UII-8 FSERV: Smallholder farmers, micro-entrepreneurs and SMEs have improved access to financial services.',
            'group' => 'Inclusive Agribusiness and Innovation',
            "target_text" => "##number## Value of additional financial services.",
            'tab' => [
                'text' => 'Additional financial services',
                'bold' => false
            ],
            'max' => false,
            'replace_value_with' => 1,
            'orders' => true,
            'dimensions' => [
                [
                    "order" => 1,
                    "dimension" => "financial services",
                    "dimension_title" => "Total value(Euros) of financial services accessed by the SHFs, micro-entrepreneurs and SMEs",
                    "target_text" => "##number## Euros as value of additional financial services accessed by smallholder farmers, MSMEs and SMEs.",
                ],
                [
                    "order" => 2,
                    "dimension" => "SHF",
                    "dimension_title" => "Newly registered SHFs",
                    "target_text" => "##number## smallholder farmers (50% women and 40% youth) have access to additional financial services.",
                ],
                [
                    "order" => 3,
                    "dimension" => "micro-entrepreneurs",
                    "dimension_title" => "Newly registered micro-entrepreneurs",
                    "target_text" => "##number## MSMEs (50% female-led; 20% young entrepreneurs) have access to additional financial services.",
                ],
                [
                    "order" => 4,
                    "dimension" => "SMEs",
                    "dimension_title" => "Newly registered SMEs",
                    "target_text" => "##number## SMEs (50% female-led) have access to additional financial services.",
                ],
            ],
        ],
        [
            'id' => 48191,
            'name' => 'Private contribution',
            'result_title' => "Private sector contribution (in kind/in cash) (€)",
            'group' => "Input Additionality",
            'target_text' => '##number## Euros as private sector contribution.',
            'tab' => [
                'text' => 'Financial value of private sector contributions (Euros)',
                'bold' => ' private sector contributions'
            ],
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
        [
            'id' => 48259,
            'name' => '2SCALE contribution',
            'result_title' => "2SCALE's Contribution (€)",
            'group' => "Input Additionality",
            'target_text' => '##number## Euros as 2SCALE contribution.',
            'tab' => [
                'text' => 'Financial value of 2SCALE contribution (Euros)',
                'bold' => ' 2SCALE contribution '
            ],
            'max' => false,
            'replace_value_with' => false,
            'orders' => false,
        ],
    ],
    'charts' => [
        'reachreact' => [
            'form_id' => 20020001,
            'title' => 'Number of Activities Reported'
        ],
        'workstream' => [
            'question_id' => 30120028,
            'title' => 'Link to Work Stream'
        ],
        'program-theme' => [
            'question_id' => 30100022,
            'title' => 'Which program theme(s) is the activity linked to?'
        ],
        'target-audience' => [
            'question_id' => 16050004,
            'title' => 'Target Audience(s)'
        ],
    ],
    'organization_form' => [
        'abc_names' => [
            'fid' => 30160001,
            'qids' => [
                'partnership_qid' => 20150001,
                'cluster_qid' => 14180001,
            ],
        ],
        'other_main_partners' => [ // Enterprise Information Form
            'fid' => 30200004,
            'qids' => [
                'partnership_qid' => 36120005,
                'enterprise_qid' => 38120005,
            ],
        ],
        'producer_organization' => [
            'fid' => 14170009,
            'qids' => [
                'partnership_qid' => 36100005,
                'producer_organization_qid' => 38140006,
            ],
        ],
    ],
    'datatables' => [
        // uii 8 & IP-A (Immediate outcome) - ET06_Indigenous Oilseeds_Tsehay MFCU
        'uii8_results_ids' => [42855, 44813, 44856, 43825, 4286, 42861, 42862, 42859, 42856, 42857, 42860, 43951],
        'ui8_dimension_ids' => [2832, 2832, 3005, 2837, 2838, 2839, 2836, 2833, 2834, 2837, 3038],
    ],
    'projects' => [
        'parent' => 8759, # 2scale program
        'childs' => [

            'NG' => [ # Nigeria
                'parent' => 8808,
                'childs' => [
                    'NG01' => 9264,
                    'NG09' => 9030,
                    'NG11' => null,
                    'NG12' => 9268,
                    'NG21' => 9351,
                    'NG22' => 9350,
                    'NG23' => 9342,
                    'NG24' => 9360,
                    'NG25' => 9354,
                    'NG26' => 9361,
                ],
            ], # Nigeria

            'GH' => [ # Ghana
                'parent' => 8761,
                'childs' => [
                    'GH09' => 8833,
                    'GH21' => 9334,
                    'GH22' => 9336,
                    'GH23' => 9338,
                    'GH24' => 9339,
                ],
            ], # Ghana

            'ET' => [ # Ethiopia
                'parent' => 8804,
                'childs' => [
                    'ET06' => 9009,
                    'ET10' => 9258,
                    'ET21' => 9259,
                    'ET22' => 9281,
                    'ET23' => 9282,
                    'ET24' => 9324, /** on rsr API this not appear */
                    'ET25' => 9326,
                    'ET26' => 9328,
                    'ET27' => 9327,
                ],
            ], # Ethiopia

            'CI' => [ # Cote d'Ivoire
                'parent' => 8805,
                'childs' => [
                    'CI21' => 9333,
                    'CI22' => 9335,
                    'CI23' => 9337,
                ],
            ], # Cote d'Ivoire

            'KE' => [ # Kenya
                'parent' => 8806,
                'childs' => [
                    'KE11' => 9206,
                    'KE21' => 9230,
                    'KE22' => 9254,
                    'KE23' => 9255,
                    'KE24' => 9257,
                    'KE25' => 9256,
                    'KE26' => 9329,
                    'KE27' => 9330,
                    'KE28' => 9331,
                    'KE29' => 9332,
                    'KE30' => 9885
                ],
            ], # Kenya

            'ML' => [ # Mali
                'parent' => 8807,
                'childs' => [
                    'ML05' => 9227,
                    'ML21' => 9243,
                    'ML22' => 9251,
                    'ML23' => 9325,
                    'ML24' => 9346,
                    'ML25' => 9347,
                    'ML26' => 9349,
                ],
            ], # Mali

            'NE' => [ # Niger
                'parent' => 8809,
                'childs' => [
                    'NE21' => null,
                    'NE22' => 9276,
                    'NE23' => 9277,
                    'NE24' => 9352,
                    'NE25' => 9353,
                    'NE26' => 9355,
                    'NE27' => 9356,
                    'NE28' => 9357,
                ],
            ], # Niger

            'BF' => [ # Burkina Faso
                'parent' => 8760,
                'childs' => [
                    'BF21' => 9266,
                    'BF22' => 9269,
                    'BF23' => null,
                    'BF24' => 9270,
                    'BF25' => 9348,
                    'BF26' => 9358,
                    'BF27' => 9359,
                    'BF28' => 9362,
                ],
            ], # Burkina Faso
        ],
    ],
];
