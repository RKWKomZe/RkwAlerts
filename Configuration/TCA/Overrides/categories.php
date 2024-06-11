<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {

        $tempColumns = [

            'tx_rkwalerts_enable_alerts' => [
                'exclude' => 0,
                'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_categories.tx_rkwalerts_enable_alerts',
                'config' => [
                    'type' => 'check',
                    'default' => 0,
                    'items' => [
                        '1' => [
                            '0' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_categories.tx_rkwalerts_enable_alerts.I.enable'
                        ]
                    ]
                ]
            ]
        ];

        // Add TCA
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_category', $tempColumns);

        // Add field
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_category','tx_rkwalerts_enable_alerts', '', 'after:parent');

    },
    'rkw_alerts'
);


