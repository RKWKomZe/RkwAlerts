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

$EM_CONF[$_EXTKEY] = array(
	'title' => 'RKW Alerts',
	'description' => 'Extension for sending news-alerts',
	'category' => 'plugin',
	'author' => 'Steffen Kroggel',
	'author_email' => 'developer@steffenkroggel.de',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '7.6.5',
	'constraints' => array(
		'depends' => array(
			'extbase' => '7.6.0-8.7.99',
			'fluid' => '7.6.0-8.7.99',
			'typo3' => '7.6.0-8.7.99',
			'rkw_basics' => '7.6.10-8.7.99',
			'rkw_mailer' => '7.6.10-8.7.99',
			'rkw_registration' => '7.6.10-8.7.99',
			'rkw_projects' => '7.6.10-8.7.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);