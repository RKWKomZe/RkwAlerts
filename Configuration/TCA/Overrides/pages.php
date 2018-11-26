<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


$tempPagesColumns = array(

    'tx_rkwalerts_send_status' => array(
        'exclude' => 0,
        'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_pages.tx_rkwalerts_send_status',
        'config' => array(
            'type' => 'check',
            'default' => 0,
            'readOnly' => 1,
            'items' => array(
                '1' => array(
                    '0' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_pages.tx_rkwalerts_send_status.I.sent'
                )
            )
        )
    ),
);
// Add TCA
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempPagesColumns);

// Add field to the existing palette
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'tx_rkwbasics_extended','tx_rkwalerts_send_status', 'after:tx_rkwsearch_no_search');

