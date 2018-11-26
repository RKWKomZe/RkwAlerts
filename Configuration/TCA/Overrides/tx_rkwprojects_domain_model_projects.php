<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$tempColumns = array(

    'tx_rkwalerts_enable_alerts' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwprojects_domain_model_projects.tx_rkwalerts_enable_alerts',
        'config' => array(
            'type' => 'check',
            'default' => 0,
            'items' => array(
                '1' => array(
                    '0' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwprojects_domain_model_projects.tx_rkwalerts_enable_alerts.I.enable'
                )
            )
        )
    ),
);
// Add TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_rkwprojects_domain_model_projects', $tempColumns);

// Add field
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('tx_rkwprojects_domain_model_projects','tx_rkwalerts_enable_alerts', '', 'after:visibility');
