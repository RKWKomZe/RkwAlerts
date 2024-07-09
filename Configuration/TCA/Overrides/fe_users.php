<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {

        $tempCols = [

            'tx_rkwalerts_alerts' => [
                'exclude' => 1,
                'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_frontenduser.tx_rkwalerts_alerts',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_rkwalerts_domain_model_alert',
                    'foreign_field' => 'frontend_user',
                    'maxitems'      => 9999,
                    'appearance' => [
                        'collapseAll' => 1,
                        'levelLinksPosition' => 'top',
                        'showSynchronizationLink' => 1,
                        'showPossibleLocalizationRecords' => 1,
                        'showAllLocalizationLink' => 1,
                        'enabledControls' => [
                            'new' => FALSE,
                        ],
                    ],
                ],
            ],

        ];

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempCols);

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
            'fe_users',
            '--div--;LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:fe_users.tab.tx_rkwalerts;, tx_rkwalerts_alerts',
            '0'
        );
    },
    'rkw_alerts'
);
