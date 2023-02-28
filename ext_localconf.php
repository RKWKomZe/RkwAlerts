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
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_CREATING_OPTIN  . 'RkwAlerts',
            RKW\RkwAlerts\Service\RkwMailService::class,
            'optInAlertUser'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_REGISTRATION_COMPLETED . 'RkwAlerts',
            RKW\RkwAlerts\Alerts\AlertManager::class,
            'saveAlertByRegistration'
        );

        $signalSlotDispatcher->connect(
            RKW\RkwAlerts\Alerts\AlertManager::class,
            \RKW\RkwAlerts\Alerts\AlertManager::SIGNAL_AFTER_ALERT_CREATED,
            RKW\RkwAlerts\Service\RkwMailService::class,
            'confirmAlertUser'
        );

        $signalSlotDispatcher->connect(
            Madj2k\FeRegister\Registration\AbstractRegistration::class,
            Madj2k\FeRegister\Registration\AbstractRegistration::SIGNAL_AFTER_REGISTRATION_ENDED,
            RKW\RkwAlerts\Alerts\AlertManager::class,
            'deleteAlertsByFrontendEndUser'
        );

        $signalSlotDispatcher->connect(
            RKW\RkwAlerts\Alerts\AlertManager::class,
            \RKW\RkwAlerts\Alerts\AlertManager::SIGNAL_AFTER_ALERT_DELETED_ALL,
            RKW\RkwAlerts\Service\RkwMailService::class,
            'cancelAllUser'
        );

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

