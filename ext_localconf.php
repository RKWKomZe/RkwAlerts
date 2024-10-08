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
            'Create',
            array(
                'Alert' => 'new, newNonCached, create, optIn',
            ),
            // non-cacheable actions
            array(
                'Alert' => 'newNonCached, create, optIn',
            )
        );


        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'RKW.' . $extKey,
            'Edit',
            array(
                'Alert' => 'list, delete, deleteconfirm',
            ),
            // non-cacheable actions
            array(
                'Alert' => 'list, delete, deleteconfirm',
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
        // ATTENTION: deactivated due to faulty mapping in TYPO3 9.5
        // Add XClasses for extending existing classes
        //=================================================================
//        // for TYPO3 12+
//        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\RKW\RkwProjects\Domain\Model\Projects::class] = [
//            'className' => \RKW\RkwAlerts\Domain\Model\Project::class
//        ];
//
//        // for TYPO3 9.5 - 11.5 only, not required for TYPO3 12
//        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
//            ->registerImplementation(
//                \RKW\RkwProjects\Domain\Model\Projects::class,
//                \RKW\RkwAlerts\Domain\Model\Project::class
//            );
//
//
//        // for TYPO3 12+
//        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\RKW\RkwProjects\Domain\Model\Pages::class] = [
//            'className' => \RKW\RkwAlerts\Domain\Model\Page::class
//        ];
//
//        // for TYPO3 9.5 - 11.5 only, not required for TYPO3 12
//        \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
//            ->registerImplementation(
//                \RKW\RkwProjects\Domain\Model\Pages::class,
//                \RKW\RkwAlerts\Domain\Model\Page::class
//            );

        //=================================================================
        // Register Hook
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \RKW\RkwAlerts\Hooks\DataHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['modifyQuery'][1721378547] = \RKW\RkwAlerts\Hooks\DataHandler::class;
        //=================================================================
        // Register Logger
        //=================================================================
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['RKW']['RkwAlerts']['writerConfiguration'] = array(

            // configuration for WARNING severity, including all
            // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
            \TYPO3\CMS\Core\Log\LogLevel::WARNING => array(

                // add a FileWriter
                'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
                    // configuration for the writer
                    'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath()  . '/log/tx_rkwalerts.log'
                )
            ),
        );

    },
    'rkw_alerts'
);

