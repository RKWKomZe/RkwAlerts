<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {

        $tempPagesColumns = [

            'tx_rkwalerts_send_status' => [
                'exclude' => 0,
                'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_pages.tx_rkwalerts_send_status',
                'config' => [
                    'type' => 'check',
                    'default' => 0,
                    'readOnly' => 1,
                    'items' => [
                        '1' => [
                            '0' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_pages.tx_rkwalerts_send_status.I.sent'
                        ]
                    ]
                ]
            ]
        ];

        // Add TCA
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempPagesColumns);
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'abstract','--linebreak--,tx_rkwalerts_send_status','after:abstract');
    },
    'rkw_alerts'
);
