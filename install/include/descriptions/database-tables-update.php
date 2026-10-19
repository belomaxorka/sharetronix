<?php
	$db_updates = array();
	
	$db_updates['3.5.0'] = array(
			'tables' => array(
					'groups_categories' => "CREATE TABLE IF NOT EXISTS `groups_categories` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`title` varchar(255) NOT NULL,
					`url` varchar(50) NOT NULL,
					`is_admin_created` int(11) NOT NULL DEFAULT  '0',
					`creator_id` int(11) NOT NULL,
					`is_locked` tinyint(1) NOT NULL DEFAULT  '0',
					PRIMARY KEY (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
						
			),
			'queries'=>array(
					"ALTER TABLE  `groups` ADD  `category_id` INT NOT NULL DEFAULT '0'",
					"INSERT IGNORE INTO `settings` (`word`, `value`) VALUES ('USERS_TO_CREATE_SUBCATEGORIES', '0')",
					"REPLACE INTO  `settings` (`word`, `value`) VALUES ('VERSION', '3.5.0')"
			)
	);
	
	$db_updates['3.6.0'] = array(
			'tables' => array(
					'post_likes' => "CREATE TABLE IF NOT EXISTS `post_likes` (
										`id` int(11) NOT NULL AUTO_INCREMENT,
										`group_id` int(11) NOT NULL DEFAULT  '0',
										`user_id` int(11) NOT NULL,
										`post_id` int(11) NOT NULL,
										`comment_id` int(11) NOT NULL DEFAULT  '0',
										`date` int(11) NOT NULL,
										PRIMARY KEY (`id`)
										) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
	
			),
			'queries'=>array(
					"REPLACE INTO  `settings` (`word`, `value`) VALUES ('VERSION', '3.6.0')"
			)
	);
	
	$db_updates['3.6.1'] = array(
			'tables' => array(),
			'queries'=>array(
					"REPLACE INTO  `settings` (`word`, `value`) VALUES ('VERSION', '3.6.1')"
			)
	);
	
	$db_updates['3.6.2'] = array(
			'tables' => array(),
			'queries'=>array(
					"REPLACE INTO  `settings` (`word`, `value`) VALUES ('VERSION', '3.6.2')"
			)
	);

	$db_updates['3.6.3'] = array(
			'tables' => array(),
			'queries'=>array(
					// Display names are stored HTML-escaped; self-registration used to store them raw.
					// Escaped rows never contain a raw <, > or ", and a bare & outside an entity is raw
					// as well, so the statement only touches raw rows and can be repeated safely.
					"UPDATE `users` SET `fullname`=REPLACE(REPLACE(REPLACE(REPLACE(`fullname`, '&', '&amp;'), '<', '&lt;'), '>', '&gt;'), '\"', '&quot;') WHERE `fullname` REGEXP '[<>\"]' OR (`fullname` LIKE '%&%' AND `fullname` NOT REGEXP '&(amp|lt|gt|quot|#0?39);')",
					// The OAuth 1.0 provider of 2.x is gone (its class since 3.0.1, the rest in 3.6.3). Its tables
					// are used by nothing; at most they keep old tokens and their secrets in plain text.
					"DROP TABLE IF EXISTS `oauth_access_token`, `oauth_request_token`, `oauth_log`",
					"DELETE FROM `plugins_tables` WHERE `table` IN ('oauth_access_token', 'oauth_request_token', 'oauth_log') AND `owner`='system'",
					// The marketplace client of the plugins, languages and themes pages is gone. The credentials it
					// saved, the password as an unsalted md5, are read by nothing.
					"DELETE FROM `settings` WHERE `word` IN ('STX_KEY', 'STX_USERNAME', 'STX_PASSWORD')",
					// plugins_installed kept the marketplace id of each plugin the marketplace installed.
					"DROP TABLE IF EXISTS `plugins_installed`",
					"DELETE FROM `plugins_tables` WHERE `table`='plugins_installed' AND `owner`='system'",
					// The MEMORY table cache belonged to the mysqlheap cache driver, which 3.6.3 removed.
					"DROP TABLE IF EXISTS `cache`",
					"DELETE FROM `plugins_tables` WHERE `table`='cache' AND `owner`='system'",
					// Settings that the installer creates since 3.5 and older installations never got; the
					// administration pages and the list of online users read them.
					"INSERT IGNORE INTO `settings` (`word`, `value`) VALUES ('ADMIN_STEALTH_MODE', '0'), ('NAME_INDENTIFICATOR', '1')",
					// `settings`.`word` is unique and a failed query aborts the updater, so replace instead of insert.
					"REPLACE INTO  `settings` (`word`, `value`) VALUES ('VERSION', '3.6.3')"
			)
	);