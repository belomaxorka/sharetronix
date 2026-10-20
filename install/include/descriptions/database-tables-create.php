<?php
	$db_tables = array();
	
	$db_tables['applications'] = "CREATE TABLE `applications` (
			  `id` int unsigned NOT NULL auto_increment,
			  `name` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `detect` varchar(50) collate utf8mb4_unicode_520_ci NOT NULL,
			  `total_posts` int unsigned NOT NULL,
			  `app_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  `consumer_key` varchar(1000) collate utf8mb4_unicode_520_ci NOT NULL,
			  `consumer_secret` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `callback_url` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `avatar` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `description` text collate utf8mb4_unicode_520_ci NOT NULL,
			  `app_website` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `organization` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `website` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `app_type` enum('','browser','client') collate utf8mb4_unicode_520_ci NOT NULL,
			  `acc_type` enum('','r','rw') collate utf8mb4_unicode_520_ci NOT NULL,
			  `use_for_login` tinyint unsigned NOT NULL,
			  `reg_date` int unsigned NOT NULL,
			  `reg_ip` bigint unsigned NOT NULL,
			  `suspended` tinyint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `app_id` (`app_id`),
			  KEY `consumer_key` (`consumer_key`(250))
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['crons'] = "CREATE TABLE `crons` (
			  `cron` varchar(10) collate utf8mb4_unicode_520_ci NOT NULL,
			  `last_run` int unsigned NOT NULL,
			  `next_run` int unsigned NOT NULL,
			  `is_running` tinyint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`cron`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['email_change_requests'] = "CREATE TABLE `email_change_requests` (
			  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			  `user_id` INT UNSIGNED NOT NULL ,
			  `new_email` VARCHAR( 100 ) NOT NULL ,
			  `confirm_key` VARCHAR( 32 ) NOT NULL ,
			  `confirm_valid` INT UNSIGNED NOT NULL ,
			  INDEX ( `user_id` , `confirm_key` )
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['groups'] = "CREATE TABLE `groups` (
			  `id` int unsigned NOT NULL auto_increment,
			  `groupname` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `title` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `avatar` varchar(200) collate utf8mb4_unicode_520_ci NOT NULL,
			  `about_me` varchar(200) collate utf8mb4_unicode_520_ci NOT NULL,
			  `is_public` tinyint unsigned NOT NULL,
			  `num_posts` int unsigned NOT NULL default '0',
			  `num_followers` int unsigned NOT NULL default '0',
			  `category_id` INT NOT NULL DEFAULT  '0',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `groupname` (`groupname`(250)),
			  KEY `is_public` (`is_public`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['groups_admins'] = "CREATE TABLE `groups_admins` (
			  `id` int unsigned NOT NULL auto_increment,
			  `group_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `group_id` (`group_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['groups_deleted'] = "CREATE TABLE `groups_deleted` (
			  `id` int unsigned NOT NULL,
			  `groupname` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `title` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `is_public` tinyint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `is_public` (`is_public`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['groups_followed'] = "CREATE TABLE `groups_followed` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `group_id` int unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  `group_from_postid` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `group_id` (`group_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['group_notifications'] = "CREATE TABLE  `group_notifications` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`notif_type` VARCHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			`to_group_id` INT UNSIGNED NOT NULL ,
			`from_user_id` INT UNSIGNED NOT NULL ,
			`date` INT UNSIGNED NOT NULL
			) ENGINE = MYISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";

	$db_tables['groups_private_members'] = "CREATE TABLE `groups_private_members` (
			  `id` int unsigned NOT NULL auto_increment,
			  `group_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  `invited_by` int unsigned NOT NULL,
			  `invited_date` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `group_id` (`group_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['invitation_codes'] = "CREATE TABLE `invitation_codes` (
			  `code` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `user_id` int NOT NULL,
			  PRIMARY KEY  (`code`),
			  KEY `network_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['ip_rates_limit'] = "CREATE TABLE `ip_rates_limit` (
			  `id` int NOT NULL auto_increment,
			  `ip` bigint NOT NULL,
			  `rate_limits` int NOT NULL,
			  `rate_limits_date` int NOT NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['notifications'] = "CREATE TABLE  `notifications` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`notif_type` VARCHAR( 30 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			`to_user_id` INT UNSIGNED NOT NULL ,
			`in_group_id` INT UNSIGNED NOT NULL ,
			`from_user_id` VARCHAR( 255 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			`notif_object_type` VARCHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
			`notif_object_id` INT UNSIGNED NOT NULL,
			`date` INT UNSIGNED NOT NULL,
			KEY `to_user_id` (`to_user_id`,`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['plugins'] = "CREATE TABLE IF NOT EXISTS  `plugins` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`name` VARCHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			`is_installed` TINYINT UNSIGNED NOT NULL ,
			`date_installed` INT UNSIGNED NOT NULL ,
			`installed_by_user_id` INT UNSIGNED NOT NULL
			) ENGINE = MYISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";

	$db_tables['plugins_cache'] = "CREATE TABLE IF NOT EXISTS  `plugins_cache` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`plugin_name` CHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			`event_name` CHAR( 50 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
			INDEX USING BTREE (  `plugin_name` ,  `event_name` )
			) ENGINE = MEMORY CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;";

	$db_tables['plugins_tables'] = "CREATE TABLE IF NOT EXISTS `plugins_tables` (
				`id` int NOT NULL AUTO_INCREMENT,
				`table` varchar(255) NOT NULL,
				`owner` varchar(255) NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `table` (`table`(250))
			) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['languages'] = "CREATE TABLE IF NOT EXISTS `languages` (
				`id` int NOT NULL AUTO_INCREMENT,
				`langkey` varchar(50) NOT NULL DEFAULT '0',
				`installed` int DEFAULT NULL,
				`version` int DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `langkey` (`langkey`)
			) ENGINE=MYISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts'] = "CREATE TABLE `posts` (
			  `id` int unsigned NOT NULL auto_increment,
			  `api_id` smallint unsigned NOT NULL default '0',
			  `user_id` int unsigned NOT NULL,
			  `group_id` int unsigned NOT NULL,
			  `message` varchar(1000) collate utf8mb4_unicode_520_ci NOT NULL,
			  `mentioned` tinyint unsigned NOT NULL default '0',
			  `attached` tinyint unsigned NOT NULL default '0',
			  `posttags` tinyint unsigned NOT NULL default '0',
			  `comments` smallint unsigned NOT NULL default '0',
			  `reshares` smallint unsigned NOT NULL default '0',
			  `likes` smallint unsigned NOT NULL default '0',
			  `date` int unsigned NOT NULL,
			  `date_lastedit` int NOT NULL,
			  `date_lastcomment` int NOT NULL,
			  `ip_addr` bigint NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `group_id` (`group_id`),
			  KEY `api_id` (`api_id`),
			  KEY `api_user_IDX` (`api_id`,`user_id`),
			  FULLTEXT KEY `message` (`message`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_attachments'] = "CREATE TABLE `posts_attachments` (
			  `id` int unsigned NOT NULL auto_increment,
			  `post_id` int unsigned NOT NULL,
			  `type` enum('link','image','videoembed','videoupload','text','file') collate utf8mb4_unicode_520_ci NOT NULL,
			  `data` text collate utf8mb4_unicode_520_ci NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `type` (`type`),
			  KEY `post_type_IDX` (`post_id`,`type`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_comments'] = "CREATE TABLE `posts_comments` (
			  `id` int unsigned NOT NULL auto_increment,
			  `api_id` smallint unsigned NOT NULL default '0',
			  `post_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  `message` text collate utf8mb4_unicode_520_ci NOT NULL,
			  `mentioned` tinyint unsigned NOT NULL,
			  `likes` int unsigned NOT NULL,
			  `posttags` tinyint unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  `ip_addr` bigint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`),
			  FULLTEXT KEY `message` (`message`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_comments_mentioned'] = "CREATE TABLE `posts_comments_mentioned` (
			  `id` int NOT NULL auto_increment,
			  `comment_id` int NOT NULL,
			  `user_id` int NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `comment_id` (`comment_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_comments_watch'] = "CREATE TABLE `posts_comments_watch` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `post_id` int unsigned NOT NULL,
			  `newcomments` smallint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_mentioned'] = "CREATE TABLE `posts_mentioned` (
			  `id` int unsigned NOT NULL auto_increment,
			  `post_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr'] = "CREATE TABLE `posts_pr` (
			  `id` int unsigned NOT NULL auto_increment,
			  `api_id` smallint unsigned NOT NULL default '0',
			  `user_id` int unsigned NOT NULL,
			  `to_user` int unsigned NOT NULL,
			  `message` varchar(1000) collate utf8mb4_unicode_520_ci NOT NULL,
			  `mentioned` tinyint unsigned NOT NULL default '0',
			  `attached` tinyint unsigned NOT NULL default '0',
			  `posttags` tinyint unsigned NOT NULL default '0',
			  `comments` smallint unsigned NOT NULL default '0',
			  `date` int unsigned NOT NULL,
			  `date_lastedit` int NOT NULL,
			  `date_lastcomment` int NOT NULL,
			  `ip_addr` bigint NOT NULL,
			  `is_recp_del` tinyint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `to_user` (`to_user`),
			  KEY `is_recp_del` (`is_recp_del`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr_attachments'] = "CREATE TABLE `posts_pr_attachments` (
			  `id` int unsigned NOT NULL auto_increment,
			  `post_id` int unsigned NOT NULL,
			  `type` enum('link','image','videoembed','videoupload','text','file') collate utf8mb4_unicode_520_ci NOT NULL,
			  `data` text collate utf8mb4_unicode_520_ci NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `type` (`type`),
			  KEY `post_type_IDX` (`post_id`,`type`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr_comments'] = "CREATE TABLE `posts_pr_comments` (
			  `id` int unsigned NOT NULL auto_increment,
			  `api_id` smallint unsigned NOT NULL default '0',
			  `post_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  `message` text collate utf8mb4_unicode_520_ci NOT NULL,
			  `mentioned` tinyint unsigned NOT NULL,
			  `posttags` tinyint unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  `ip_addr` bigint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr_comments_mentioned'] = "CREATE TABLE `posts_pr_comments_mentioned` (
			  `id` int NOT NULL auto_increment,
			  `comment_id` int NOT NULL,
			  `user_id` int NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `comment_id` (`comment_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr_comments_watch'] = "CREATE TABLE `posts_pr_comments_watch` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `post_id` int unsigned NOT NULL,
			  `newcomments` smallint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['posts_pr_mentioned'] = "CREATE TABLE `posts_pr_mentioned` (
			  `id` int unsigned NOT NULL auto_increment,
			  `post_id` int unsigned NOT NULL,
			  `user_id` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['post_tags'] = "CREATE TABLE  `post_tags` (
				  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
				  `tag_name` VARCHAR( 200 ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL ,
				  `user_id` INT UNSIGNED NOT NULL,
				  `group_id` INT UNSIGNED NOT NULL,
				  `post_id` INT UNSIGNED NOT NULL ,
				  `date` INT UNSIGNED NOT NULL,
				  PRIMARY KEY (  `id` ),
				  INDEX (tag_name)
				 ) ENGINE = MYISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci";

	$db_tables['post_favs'] = "CREATE TABLE `post_favs` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `post_type` enum('public','private') collate utf8mb4_unicode_520_ci NOT NULL,
			  `post_id` int unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `post_type` (`post_type`,`post_id`),
			  KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['post_userbox'] = "CREATE TABLE `post_userbox` (
  			  `id` bigint unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `post_id` int unsigned NOT NULL,
  			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`),
			  KEY `post_id` (`post_id`),
			  KEY `user_post_IDX` (`user_id`,`post_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['pubsubhubbub_subscriptions'] = "CREATE TABLE `pubsubhubbub_subscriptions` (
			  `id` int unsigned NOT NULL auto_increment,
			  `feed_url` varchar(500) collate utf8mb4_unicode_520_ci NOT NULL,
			  `status` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `last_status_date` int unsigned NOT NULL,
			  `parse_needed` tinyint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `feed_url` (`feed_url`(250)),
			  KEY `status` (`status`),
 			  KEY `parse_needed` (`parse_needed`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['searches'] = "CREATE TABLE `searches` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `search_key` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `search_string` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `search_url` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `added_date` int unsigned NOT NULL,
			  `total_hits` mediumint unsigned NOT NULL default '0',
			  `last_results` mediumint unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`,`search_key`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['settings'] = "CREATE TABLE `settings` (
			  `word` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `value` text collate utf8mb4_unicode_520_ci NOT NULL,
			  UNIQUE KEY `word` (`word`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['unconfirmed_registrations'] = "CREATE TABLE `unconfirmed_registrations` (
			  `id` int unsigned NOT NULL auto_increment,
			  `email` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `fullname` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `confirm_key` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `invited_code` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `date` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `email` (`email`(250))
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users'] = "CREATE TABLE `users` (
			  `id` int unsigned NOT NULL auto_increment,
			  `facebook_uid` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `twitter_uid` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `email` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `username` varchar(200) collate utf8mb4_unicode_520_ci NOT NULL,
			  `password` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `fullname` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `avatar` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `about_me` text collate utf8mb4_unicode_520_ci NOT NULL,
			  `tags` text collate utf8mb4_unicode_520_ci NOT NULL,
			  `gender` enum('','m','f') collate utf8mb4_unicode_520_ci NOT NULL,
			  `birthdate` date NOT NULL,
			  `position` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `location` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `language` varchar(5) collate utf8mb4_unicode_520_ci NOT NULL,
			  `timezone` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `num_posts` int unsigned NOT NULL,
			  `num_followers` int unsigned NOT NULL,
			  `reg_date` int unsigned NOT NULL,
			  `reg_ip` bigint NOT NULL,
			  `lastlogin_date` int unsigned NOT NULL,
			  `lastlogin_ip` bigint NOT NULL,
			  `lastpost_date` int unsigned NOT NULL,
			  `lastemail_date` int unsigned NOT NULL,
			  `lastclick_date` int unsigned NOT NULL,
			  `lastclick_date_newest_post` int unsigned NOT NULL,
			  `pass_reset_key` varchar(32) collate utf8mb4_unicode_520_ci NOT NULL,
			  `pass_reset_valid` int unsigned NOT NULL,
			  `active` tinyint unsigned NOT NULL default '1',
			  `is_network_admin` tinyint unsigned NOT NULL default '0',
			  `is_posts_protected` tinyint unsigned NOT NULL default '0',
			  `is_profile_protected` tinyint unsigned NOT NULL default '0',
			  `is_dm_protected` tinyint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `email` (`email`(250)),
			  UNIQUE KEY `username` (`username`),
			  KEY `active` (`active`),
			  KEY `num_followers` (`num_followers`),
			  KEY `facebook_uid` (`facebook_uid`),
			  KEY `twitter_uid` (`twitter_uid`),
			  KEY `pass_reset_IDX` (`pass_reset_key`,`pass_reset_valid`),
			  KEY `lastclick_date` (`lastclick_date`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users_dashboard_tabs'] = "CREATE TABLE `users_dashboard_tabs` (
			  `user_id` int unsigned NOT NULL,
			  `tab` enum('','all','@me','private','commented','feeds','tweets','notifications') collate utf8mb4_unicode_520_ci NOT NULL,
			  `state` tinyint unsigned NOT NULL,
			  `newposts` smallint unsigned NOT NULL default '0',
			  PRIMARY KEY  (`user_id`,`tab`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users_details'] = "CREATE TABLE `users_details` (
			  `user_id` int unsigned NOT NULL,
			  `website` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `work_phone` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `personal_phone` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `personal_email` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `im_skype` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `im_gtalk` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `prof_linkedin` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `prof_facebook` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  `prof_twitter` varchar(255) collate utf8mb4_unicode_520_ci NOT NULL,
			  PRIMARY KEY  (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users_followed'] = "CREATE TABLE `users_followed` (
			  `id` int unsigned NOT NULL auto_increment,
			  `who` int unsigned NOT NULL,
			  `whom` int unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  `whom_from_postid` int unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `who` (`who`),
			  KEY `whom` (`whom`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users_invitations'] = "CREATE TABLE `users_invitations` (
			  `id` int unsigned NOT NULL auto_increment,
			  `user_id` int unsigned NOT NULL,
			  `date` int unsigned NOT NULL,
			  `recp_name` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `recp_email` varchar(100) collate utf8mb4_unicode_520_ci NOT NULL,
			  `recp_is_registered` tinyint unsigned NOT NULL default '0',
			  `recp_user_id` int unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`),
			  KEY `user_id` (`user_id`,`recp_is_registered`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";

	$db_tables['users_notif_rules'] = "CREATE TABLE `users_notif_rules` (
			  `user_id` int unsigned NOT NULL,
			  `ntf_them_if_i_follow_usr` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_comment` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_edt_profl` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_edt_pictr` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_create_grp` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_them_if_i_join_grp` tinyint unsigned NOT NULL COMMENT '0-off, 1-on',
			  `ntf_me_if_u_follows_me` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_follows_u2` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_commments_me` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_commments_m2` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_edt_profl` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_edt_pictr` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_creates_grp` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_joins_grp` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_invit_me_grp` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_posts_qme` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_posts_prvmsg` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_if_u_registers` tinyint unsigned NOT NULL COMMENT '0-off, 2-msg, 3-mail, 1-both',
			  `ntf_me_on_post_like` tinyint unsigned NOT NULL DEFAULT  '0' COMMENT  '0-off, 1-both, 2-post,  3-email',
			  `ntf_me_on_comment_like` tinyint unsigned NOT NULL DEFAULT  '0' COMMENT  '0-off, 1-both, 2-post,  3-email',
			  PRIMARY KEY  (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['groups_categories'] = "CREATE TABLE IF NOT EXISTS `groups_categories` (
			`id` int NOT NULL AUTO_INCREMENT,
			`title` varchar(255) NOT NULL,
			`url` varchar(50) NOT NULL,
			`is_admin_created` int NOT NULL DEFAULT  '0',
			`creator_id` int NOT NULL,
			`is_locked` tinyint NOT NULL DEFAULT  '0',
			PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	
	$db_tables['post_likes'] = "CREATE TABLE IF NOT EXISTS `post_likes` (
			`id` int NOT NULL AUTO_INCREMENT,
			`group_id` int NOT NULL DEFAULT  '0',
			`user_id` int NOT NULL,
			`post_id` int NOT NULL,
			`comment_id` int NOT NULL DEFAULT  '0',
			`date` int NOT NULL,
			PRIMARY KEY (`id`),
			KEY `post_id` (`post_id`),
			KEY `user_id` (`user_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;";
	