<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function($extKey)
    {

        //=================================================================
        // Configure Plugins
        //=================================================================
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'RKW.' . $extKey,
            'Rkwalerts',
            array(
                'Alert' => 'new, newNonCached, list, create, delete, deleteconfirm, optIn',
            ),
            // non-cacheable actions
            array(
                'Alert' => 'newNonCached, list, create, delete, deleteconfirm, optIn',
            )
        );


        //=================================================================
        // Configure Signal-Slots
        //=================================================================
        /**
         * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher
         */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_CREATING_OPTIN_EXISTING_USER  . 'RkwAlerts',
            'RKW\\RkwAlerts\\Service\\RkwMailService',
            'optInAlertUser'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_CREATING_OPTIN_USER  . 'RkwAlerts',
            'RKW\\RkwAlerts\\Service\\RkwMailService',
            'optInAlertUser'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_USER_REGISTER_GRANT . 'RkwAlerts',
            'RKW\\RkwAlerts\\Alerts\\AlertManager',
            'saveAlertByRegistration'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwAlerts\\Alerts\\AlertManager',
            \RKW\RkwAlerts\Alerts\AlertManager::SIGNAL_AFTER_ALERT_CREATED,
            'RKW\\RkwAlerts\\Service\\RkwMailService',
            'confirmAlertUser'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwRegistration\\Tools\\Registration',
            \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_DELETING_USER,
            'RKW\\RkwAlerts\\Alerts\\AlertManager',
            'deleteAlertsByFrontendEndUser'
        );

        $signalSlotDispatcher->connect(
            'RKW\\RkwAlerts\\Alerts\\AlertManager',
            \RKW\RkwAlerts\Alerts\AlertManager::SIGNAL_AFTER_ALERT_DELETED_ALL,
            'RKW\\RkwAlerts\\Service\\RkwMailService',
            'cancelAllUser'
        );


        //=================================================================
        // Register Hook
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \RKW\RkwAlerts\Hooks\DataHandler::class;


        //=================================================================
        // Register Command Controller
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'RKW\\RkwAlerts\\Controller\\SendCommandController';

        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['RKW']['RkwAlerts']['writerConfiguration'] = array(

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG => array(

                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
                    // configuration for the writer
                    'logFile' => 'typo3temp/var/logs/tx_rkwalerts.log'
                )
            ),
        );

    },
    $_EXTKEY
);

