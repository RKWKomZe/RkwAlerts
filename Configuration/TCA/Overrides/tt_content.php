<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function (string $extKey) {
        //=================================================================
        // Register Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'RKW.RkwAlerts',
            'Create',
            'RKW Alerts: Create'
        );

        //=================================================================
        // Register Plugin
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
            'RKW.RkwAlerts',
            'Edit',
            'RKW Alerts: Edit'
        );

    },
    'rkw_alerts'
);
