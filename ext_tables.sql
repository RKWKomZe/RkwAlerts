#
# Table structure for table 'tx_rkwalerts_domain_model_alert'
#
CREATE TABLE tx_rkwalerts_domain_model_alert (

	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	frontend_user int(11) unsigned DEFAULT '0' NOT NULL,
	category int(11) unsigned DEFAULT '0',

	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
    deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l10n_parent int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource mediumblob,

	PRIMARY KEY (uid),
	KEY parent (pid),
    KEY language (l10n_parent,sys_language_uid)

);


#
# Table structure for table 'tx_news_domain_model_news'
#
CREATE TABLE tx_news_domain_model_news (
	tx_rkwalerts_send_status int(11) unsigned DEFAULT '0' NOT NULL,
);


#
# Table structure for table 'sys_category'
#
CREATE TABLE sys_category (
	tx_rkwalerts_enable_alerts int(11) unsigned DEFAULT '0' NOT NULL,
);


#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users
(
    tx_rkwalerts_alerts varchar(255) DEFAULT '' NOT NULL,
);


