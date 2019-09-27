<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'RKW.' . $_EXTKEY,
	'Rkwalerts',
	array(
		'Alerts' => 'index, list, newInit, newAjax, create, delete, deleteconfirm, optIn',
		
	),
	// non-cacheable actions
	array(
		'Alerts' => 'new, list, newAjax, create, delete, deleteconfirm, optIn',
	)
);


// register command controller (cronjob)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'RKW\\RkwAlerts\\Controller\\SendCommandController';


// set logger
$GLOBALS['TYPO3_CONF_VARS']['LOG']['RKW']['RkwAlerts']['writerConfiguration'] = array(

    // configuration for WARNING severity, including all
    // levels with higher severity (ERROR, CRITICAL, EMERGENCY)
    \TYPO3\CMS\Core\Log\LogLevel::DEBUG => array(
        // add a FileWriter
        'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
            // configuration for the writer
            'logFile' => 'typo3temp/logs/tx_rkwalerts.log'
        )
    ),
);


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
    'RKW\\RkwAlerts\\Controller\\AlertsController',
    'createAlert'
);

$signalSlotDispatcher->connect(
    'RKW\\RkwRegistration\\Tools\\Registration',
    \RKW\RkwRegistration\Tools\Registration::SIGNAL_AFTER_DELETING_USER,
    'RKW\\RkwAlerts\\Controller\\AlertsController',
    'removeAllOfUserSignalSlot'
);

$signalSlotDispatcher->connect(
    'RKW\\RkwAlerts\\Controller\\AlertsController',
    \RKW\RkwAlerts\Controller\AlertsController::SIGNAL_AFTER_ALERTS_CANCELED_USER,
    'RKW\\RkwAlerts\\Service\\RkwMailService',
    'cancelAllUser'
);
