<?php
return [
	'ctrl' => [
		'title'	=> 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alert',
		'label' => 'frontend_user',
		'label_alt' => 'project',
		'label_alt_force' => 1,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => true,

		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
		'enablecolumns' => [

		],
		'searchFields' => 'frontend_user,topic,',
		'iconfile' => 'EXT:rkw_alerts/Resources/Public/Icons/tx_rkwalerts_domain_model_alert.gif'
	],
	'types' => [
		'1' => ['showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, frontend_user, project, ']
	],
	'palettes' => [
		'1' => ['showitem' => '']
	],
	'columns' => [

		'sys_language_uid' => [
			'exclude' => 1,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => [
					['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
					['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
				]
			]
		],
		'l10n_parent' => [
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['', 0]
				],
				'foreign_table' => 'tx_rkwalerts_domain_model_alert',
				'foreign_table_where' => 'AND tx_rkwalerts_domain_model_alert.pid=###CURRENT_PID### AND tx_rkwalerts_domain_model_alert.sys_language_uid IN (-1,0)',
			],
		],
		'l10n_diffsource' => [
			'config' => [
				'type' => 'passthrough',
			]
		],
		'frontend_user' => [
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alert.frontend_user',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'fe_users',
                'foreign_table_where' => 'AND fe_users.disable = 0 ORDER BY username ASC',
                'minitems' => 1,
				'maxitems' => 1,
            ]
		],
        'category' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:rkw_events/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alert.category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'sys_category',
                'maxitems' => 1,
                'size' => 10,
                'treeConfig' => [
                    'parentField' => 'pid',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => false,
                        //'nonSelectableLevels' => '0,1',
                    ],
                ],
            ],
        ],

	]
];
