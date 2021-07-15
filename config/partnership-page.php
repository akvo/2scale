<?php

$ROOT_API = env('RSR_API_ROOT', '');

return [
  'partnership_names' => [
    // * Burkina Faso
    'BF21' => 'Cassava partnership with Nanalim',
    'BF22' => 'Soybean partnership with SIATOL',
    'BF23' => 'Maize partnership with FAGRI (Faso Agriculture et Intrants) (suspended)',
    'BF24' => 'Groundnuts partnership with INNOFASO',
    'BF25' => 'Rice partnership with NEBNOOMA',
    'BF26' => 'Vegetables partnership with BioProtect',
    'BF27' => 'Maize partnership with AgroServ Industrie',
    'BF28' => 'Poultry partnership with Société Burkinabé de Productions Animales (SOBUPRA)',
    // * eol Burkina Faso

    // * Côte D'ivoire
    'CI21' => 'Groundnuts partnership with K\'CHIBO SARL (suspended)',
    'CI22' => 'Multi-champion rice partnership',
    'CI23' => 'Vegetable partnership with Canaan Land',
    'CI24' => null,
    'CI25' => null,
    // * eol Côte D'ivoire

    // * Ethiopia
    'ET06' => 'Indigenous oilseed partnership with Tsehay Multipurpose Farmers’ Cooperative Union (TMFCU)',
    'ET10' => 'Sorghum partnership with Setit Humera and Dansha Aurora farmer unions (suspended)',
    'ET21' => 'Spices and herbs partnership with Damascene Essential Oil Processing',
    'ET22' => 'Maize partnership with East African Tiger Brands Industry PLC (EATBI)',
    'ET23' => 'Honey partnership with Bench Maji Coffee, Spice and Honey Farmers’ Cooperatives Union Ltd.',
    'ET24' => 'Beans parntership with Ras-Gaint Farmers\' Cooperative Union',
    'ET25' => 'Teff partnership with Kesem Multipurpose Farmers’ Cooperative Union (KMFCU)',
    'ET26' => 'Dairy partnership with Ever Green Milk Production and Processing (EGMPP) plc.',
    'ET27' => 'Poultry partnership with Chico Meat',
    'ET28' => 'Vegetable partnership with Awash Olana Multipurpose Farmers’ cooperative Union (AOFCU)_x000D_',
    // * eol Ethiopia

    // * Ghana
    'GH09' => 'Sorghum partnership with Faranaya Agribusiness Limited',
    'GH21' => 'Maize partnership with KEDAN',
    'GH22' => 'Rice partnership with Tamanaa Company',
    'GH23' => 'Soybean partnership with Vester Oil Mills Limited',
    'GH24' => 'Poultry partnership with Rockland Farms',
    // * eol Ghana

    // * Kenya
    'KE11' => 'Dairy partnership with Kieni Dairy Products Limited (KDPL)',
    'KE21' => 'Africa indigenous vegetable partnership with Sweet N Dried Enterprises Limited (SnD)',
    'KE22' => 'Groundnuts partnership with Batian Nuts Limited (BNL)',
    'KE23' => 'Vegetable partnership with Neighbourhood Freshmart Limited',
    'KE24' => 'Soybean partnership with Prosoya Limited',
    'KE25' => 'Poultry partnership with Homerange Poultry Kenya',
    'KE26' => 'Soybean partnership with Equatorial Nuts Processors',
    'KE27' => 'Beans partnership with Yash Commodities Limited (suspended)',
    'KE28' => 'Dairy partnership with Meru Dairy Union (MDU)',
    'KE29' => 'Cassava partnership with Mhogo Foods Ltd.',
    'KE30' => 'Sorghum and Pearl millet partnership with Tegemeo Cereals Enterprise Ltd',
    // * eol Kenya

    // * Mali
    'ML05' => 'Vegetables partnership with Service Commercial Silvain (SCS) Limited',
    'ML06' => 'Soybean partnership with EKT(suspended)',
    'ML21' => 'Rice partnership with Siguida Yeelen',
    'ML22' => 'Dairy partnership with Translait',
    'ML23' => 'Soybean partnership with Keitala Négoce',
    'ML24' => 'Fresh juice partnership with Zabbaan',
    'ML25' => 'Fonio partnership with UTC (Unité de Transformation des Céréales) and UCODAL(Unité de Transformation et de Conditionnement des Denrées Alimentaires)',
    'ML26' => 'Rice partnership with SOPROTRILAD (Société de Production et de Transformation du Riz dans la zone du Lac DEBO)',
    'ML27' => 'Poultry partnership with Wasaso Cooperative',
    // * eol Mali

    // * Niger
    'NE21' => 'Dairy partnership with RAB company (suspended)',
    'NE22' => 'Cassava partnership with COPROMA',
    'NE23' => 'Groundnuts partnership with AINOMA',
    'NE24' => 'Moringa partnership with Nassaraoua and Goroubi',
    'NE25' => 'Poultry partnership with AVINIGER',
    'NE26' => 'Potato partnership with CCPHN',
    'NE27' => 'Poultry partnership with NUSEB Integrated Poultry Farm',
    'NE28' => 'Millet partnership with The Federation of Unions of Farmer Groups of Niger (FUGPN-Mooriben)',
    // * eol Niger

    // * Nigeria
    'NG01' => 'Dairy partnership with FrieslandCampina Wamco Nigeria Plc.',
    'NG09' => 'Sorghum partnership with aggregators (Nalmaco Nigeria Ltd & Adefunke-Desh Ltd Nigeria)',
    'NG11' => 'Vegetable partnership with Evergreen',
    'NG12' => 'Onions partnership with Tays Foods Limited (TFL)',
    'NG21' => 'Cassava partnership with Promise Point General Trading Nigeria Ltd.',
    'NG22' => 'Groundnuts partnership with Ladipo & Lawani (L&L) Foods Nigeria Ltd.',
    'NG23' => 'Plantain partnership with Crystal Dominion Foods Ltd.',
    'NG24' => 'Dairy partnership with Nestlé Nigeria Plc.',
    'NG25' => 'Oil palm partnership with Okomu Oil Palm Company',
    'NG26' => 'Cassava partnership with Cato Foods',
    'NG27' => 'Maize partnership with AFEX Commodity Limited',
    // * eol Nigeria
  ],
  'impact_charts' => [
    # MAX is aggregation type, if false aggregation is SUM (default)
    [
      'id' => 'UII-1',
      'name' => 'UII1 - BoP',
      'group' => 'Food and Nutrition Security',
      'target_text' => '##number## BoP consumers with improved access to affordable food products.',
      'max' => true,
      'replace_value_with' => false,
      'orders' => false,
    ],
    [
      'id' => 'UII-2',
      'name' => 'UII2 - SHF',
      'group' => 'Food and Nutrition Security',
      'target_text' => '##number## smallholder farmers (50% women, 40% youth) with improved agricultural productivity and access to markets.',
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
      'target_text' => '##number## non-farming jobs in targeted agribusiness clusters and value chains (10,000 women; 8,000 youth).',
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
      'target_text' => '##number## Euros as value of additional financial services accessed by smallholder farmers, MSMEs and SMEs.',
      'max' => false,
      'replace_value_with' => 1,
      'orders' => true,
      'dimensions' => [
        [
          "order" => 1,
          "dimension" => "Total value",
          "target_text" => "##number## Euros as value of additional financial services accessed by smallholder farmers, MSMEs and SMEs.",
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
