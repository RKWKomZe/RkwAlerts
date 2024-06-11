<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {
        //=================================================================
        // Register Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'Create',
            'RKW Alerts: Create'
        );

        //=================================================================
        // Register Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            $extKey,
            'Edit',
            'RKW Alerts: Edit'
        );


        // Recommendation
        $pluginSignature = str_replace('_','', $extKey) . '_create';
        $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
        $fileName = 'FILE:EXT:' . $extKey . '/Configuration/FlexForms/flexform_alerts-create.xml';
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
            $pluginSignature,
            $fileName
        );

    },
    'rkw_alerts'
);
