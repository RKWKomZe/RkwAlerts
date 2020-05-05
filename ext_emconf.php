<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "rkw_alerts"
 *
 * Auto generated by Extension Builder 2015-08-12
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
	'title' => 'RKW Alerts',
	'description' => 'Extension for sending news-alerts',
	'category' => 'plugin',
	'author' => 'Steffen Kroggel',
	'author_email' => 'developer@steffenkroggel.de',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '8.7.3',
	'constraints' => [
		'depends' => [
			'typo3' => '7.6.0-8.7.99',
			'rkw_basics' => '8.7.12-8.7.99',
			'rkw_mailer' => '8.7.25-8.7.99',
			'rkw_registration' => '8.7.0-8.7.99',
			'rkw_projects' => '8.7.0-8.7.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
];