<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_rkwalerts_domain_model_alerts', 'EXT:rkw_alerts/Resources/Private/Language/locallang_csh_tx_rkwalerts_domain_model_alerts.xlf');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_rkwalerts_domain_model_alerts');
$GLOBALS['TCA']['tx_rkwalerts_domain_model_alerts'] = array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alerts',
		'label' => 'frontend_user',
		'label_alt' => 'project',
		'label_alt_force' => 1,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,

		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',

		'enablecolumns' => array(

		),
		'searchFields' => 'frontend_user,topic,',
		'iconfile' => 'EXT:rkw_alerts/Resources/Public/Icons/tx_rkwalerts_domain_model_alerts.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, frontend_user, project',
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, frontend_user, project, '),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
	
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				),
			),
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_rkwalerts_domain_model_alerts',
				'foreign_table_where' => 'AND tx_rkwalerts_domain_model_alerts.pid=###CURRENT_PID### AND tx_rkwalerts_domain_model_alerts.sys_language_uid IN (-1,0)',
			),
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),

		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),

		'frontend_user' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alerts.frontend_user',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'fe_users',
                'foreign_table_where' => 'AND fe_users.disable = 0 ORDER BY username ASC',
                'minitems' => 1,
				'maxitems' => 1,
            ),
		),
		'project' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rkw_alerts/Resources/Private/Language/locallang_db.xlf:tx_rkwalerts_domain_model_alerts.project',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'tx_rkwprojects_domain_model_projects',
                'foreign_table_where' => 'AND tx_rkwprojects_domain_model_projects.hidden = 0 AND tx_rkwprojects_domain_model_projects.deleted = 0 ORDER BY tx_rkwprojects_domain_model_projects.name ASC',
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		
	),
);
