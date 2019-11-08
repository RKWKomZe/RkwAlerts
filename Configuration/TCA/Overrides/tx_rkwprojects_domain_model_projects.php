<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$tempColumns = [

    'tx_rkwalerts_enable_alerts' => [
        'exclude' => 0,
        'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwprojects_domain_model_projects.tx_rkwalerts_enable_alerts',
        'config' => [
            'type' => 'check',
            'default' => 0,
            'items' => [
                '1' => [
                    '0' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwprojects_domain_model_projects.tx_rkwalerts_enable_alerts.I.enable'
                ]
            ]
        ]
    ]
];

// Add TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_rkwprojects_domain_model_projects', $tempColumns);

// Add field
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_rkwprojects_domain_model_projects','tx_rkwalerts_enable_alerts', '', 'after:visibility');
