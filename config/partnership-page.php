<?php

$ROOT_API = env('RSR_API_ROOT', '');

return [
  'parnetship_name' => [
    'code' => 'name',
  ],
  'impact_react_charts' => [
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
      'target_text' => '##number##',
      'max' => false,
      'replace_value_with' => 1,
      'orders' => true,
      'dimensions' => [
        [
          "order" => 1,
          "dimension" => "financial services",
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
