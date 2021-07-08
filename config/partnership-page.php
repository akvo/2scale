<?php

$ROOT_API = env('RSR_API_ROOT', '');

return [
  'partnership_names' => [
    // * Burkina Faso
    'BF21' => 'Cassava partnership with Nanalim',
    'BF22' => 'Soybean partnership with SIATOL',
    'BF23' => 'Maize partnership with FAGRI (Faso Agriculture et Intrants)',
    'BF24' => 'Groundnuts partnership with INNOFASO',
    'BF25' => 'Rice partnership with NEBNOOMA',
    'BF26' => 'Vegetables partnership with BioProtect ',
    'BF27' => 'Maize partnership with AgroServ Industrie',
    'BF28' => 'Poultry partnership with Société Burkinabé de Productions Animales (SOBUPRA)',
    // * eol Burkina Faso

    // * Côte D'ivoire
    'CI21' => 'Groundnuts partnership with K\'CHIBO SARL',
    'CI22' => 'Multi-champion rice partnership',
    'CI23' => 'Vegetable partnership with Canaan Land',
    'CI24' => null,
    'CI25' => null,
    // * eol Côte D'ivoire

    // * Ethiopia
    'ET06' => null,
    'ET10' => null,
    'ET21' => null,
    'ET22' => null,
    'ET23' => null,
    'ET24' => null,
    'ET25' => null,
    'ET26' => null,
    'ET27' => null,
    'ET28' => null,
    // * eol Ethiopia

    // * Ghana
    'GH09' => null,
    'GH21' => null,
    'GH22' => null,
    'GH23' => null,
    'GH24' => null,
    // * eol Ghana

    // * Kenya
    'KE11' => 'Dairy partnership with Kieni Dairy Products Limited (KDPL)',
    'KE21' => 'African indigenous vegetables partnership with Sweet n\' Dried',
    'KE22' => 'Groundnuts partnership with Batian Nuts Limited',
    'KE23' => 'Vegetable partnership with Neighbourhood Freshmart Limited',
    'KE24' => null,
    'KE25' => 'Poultry partnership with Homerange Poultry Kenya',
    'KE26' => null,
    'KE27' => null,
    'KE28' => null,
    'KE29' => null,
    'KE30' => null,
    // * eol Kenya

    // * Mali
    'ML05' => null,
    'ML06' => null,
    'ML21' => null,
    'ML22' => null,
    'ML23' => null,
    'ML24' => null,
    'ML25' => null,
    'ML26' => null,
    'ML27' => null,
    // * eol Mali

    // * Niger
    'NE21' => null,
    'NE22' => null,
    'NE23' => null,
    'NE24' => null,
    'NE25' => null,
    'NE26' => null,
    'NE27' => null,
    'NE28' => null,
    // * eol Niger

    // * Nigeria
    'NG01' => null,
    'NG09' => null,
    'NG11' => null,
    'NG12' => null,
    'NG21' => null,
    'NG22' => null,
    'NG23' => null,
    'NG24' => null,
    'NG25' => null,
    'NG26' => null,
    'NG27' => null,
    // * eol Nigeria
  ],
  'impact_charts' => [
    # MAX is aggregation type, if false aggregation is SUM (default)
    [
      'id' => 'UII-1',
      'name' => 'UII1 - BoP',
      'group' => 'Food and Nutrition Security',
      'target_text' => '##number## BoP consumers with improved access to food products.',
      'max' => true,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-2',
      'name' => 'UII2 - SHF',
      'group' => 'Food and Nutrition Security',
      'target_text' => '##number## smallholder farmers (50% women, 40% youth) with improved productivity and access to markets.',
      'max' => true,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-3',
      'name' => 'UII3 - EEP',
      'group' => 'Food and Nutrition Security',
      'target_text' => 'Eco-efficient farming practices adopted in a new area of ##number## hectares.',
      'max' => true,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-4',
      'name' => 'UII4 - SME',
      'group' => 'Inclusive Agribusiness and Innovation',
      'target_text' => '##number## SMEs driving inclusive business in target industries (125 women-led/women-owned).',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-5',
      'name' => 'UII5 - NONFE',
      'group' => 'Inclusive Agribusiness and Innovation',
      'target_text' => '##number## non-farming jobs in targeted agribusiness clusters and value chains (10,000 women; 8,000youth).',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-6',
      'name' => 'UII6 - MSME',
      'group' => 'Inclusive Agribusiness and Innovation',
      'target_text' => '##number## micro entrepreneurs/SMEs (2,500 women-led/women-owned, 1,000 young entrepreneurs) associated with partnerships.',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-7',
      'name' => 'UII7 - INNO',
      'group' => 'Inclusive Agribusiness and Innovation',
      'target_text' => '##number## non-farming innovations adopted.',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-8',
      'name' => 'UII8 - FSERV',
      'group' => 'Inclusive Agribusiness and Innovation',
      'target_text' => '##number## Euros as value of additional financial services.',
      'max' => false,
      'replace_value_with' => 1,
      'orders' => true,
      'dimensions' => [
        [
          "order" => 1,
          "dimension" => "Total value",
          "target_text" => "##number## Euros as value of additional financial services.",
        ],
        [
          "order" => 2,
          "dimension" => "SHF",
          "target_text" => "##number## smallholder farmers (50% women and 40% youth) have access to additional financial services.",
        ],
        [
          "order" => 3,
          "dimension" => "micro-entrepreneurs",
          "target_text" => "##number## MSMEs (50% female-led; 20% young entrepreneurs) have access to additional financial services.",
        ],
        [
          "order" => 4,
          "dimension" => "SMEs",
          "target_text" => "##number## SMEs (50% female-led) have access to additional financial services.",
        ],
      ],
    ],
    [
      'id' => 'Private sector contribution',
      'name' => 'Private contribution',
      'group' => "Input Additionality",
      'target_text' => '##number## Euros as private sector contribution.',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
  ],
  [
      'id' => '2SCALE\'s Contribution',
      'name' => '2SCALE contribution',
      'group' => "Input Additionality",
      'target_text' => '##number## Euros as 2SCALE contribution.',
      'max' => false,
      'replace_value_with' => false,
      'orders' => false,
  ],
  ],
  'text_visual' => [
    // * Agri-Business Cluster Form
    'abc_names' => [
      'fid' => 30160001,
      'qids' => [
        'partnership_qid' => 20150001,
        'cluster_qid' => 14180001,
      ],
    ],
    // * Enterprise Information Form
    'other_main_partners' => [
      'fid' => 30200004,
      'qids' => [
        'partnership_qid' => 36120005,
        'enterprise_qid' => 38120005,
      ],
    ],
    // * Producer Organization Information Form
    'producer_organization' => [
      'fid' => 14170009,
      'qids' => [
        'partnership_qid' => 36100005,
        'producer_organization_qid' => 38140006,
      ],
    ],
    // * Producer Organization Information Form
    'sector_focus' => [
      'fid' => 20020001,
      'qids' => [
        'partnership_qid' => 80001,
        'sector_qid' => 16040001,
      ],
    ],
  ],
];
