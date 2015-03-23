
CREATE DATABASE IF NOT EXISTS `dankestdb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `dankestdb`;

CREATE TABLE IF NOT EXISTS `action_log` (
  `action_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `user_id_affected` int(10) unsigned DEFAULT NULL,
  `post_id_affected` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(250) NOT NULL,
  `log_message` text,
  `tsc` int(10) unsigned NOT NULL,
  PRIMARY KEY (`action_id`),
  KEY `user_id_affected` (`user_id_affected`),
  KEY `user_id_action` (`user_id`,`action_type`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `approval_votes` (
  `vote_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `thevote` tinyint(4) NOT NULL,
  PRIMARY KEY (`vote_id`),
  KEY `post_id_2` (`post_id`),
  KEY `thevote_postid` (`thevote`,`post_id`),
  KEY `postid_userid` (`post_id`,`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `thecomment` text NOT NULL,
  `rawcomment` text NOT NULL,
  `posted_ts` int(10) unsigned NOT NULL,
  `updated_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`comment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `file_uniqid` varchar(36) NOT NULL,
  `post_id` int(10) unsigned NOT NULL,
  `file_path` varchar(250) NOT NULL,
  `file_url` varchar(250) NOT NULL,
  `image_width` smallint(5) unsigned DEFAULT NULL,
  `image_height` smallint(5) unsigned DEFAULT NULL,
  `duration` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `invite_codes` (
  `code_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `thecode` varchar(250) NOT NULL,
  `theemail` varchar(250) NOT NULL,
  `beenused` tinyint(1) NOT NULL DEFAULT '0',
  `tsc` int(10) unsigned NOT NULL,
  `tsu` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`code_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `login_flood_control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddr` varchar(64) NOT NULL,
  `attempts` int(11) NOT NULL,
  `tsc` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ipaddr` (`ipaddr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `post_type` varchar(20) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `visibility` tinyint(4) NOT NULL DEFAULT '6',
  `thetext` text,
  `rawtext` text,
  `nsfw` tinyint(1) NOT NULL DEFAULT '0',
  `posted_ts` int(10) unsigned NOT NULL,
  `updated_ts` int(10) unsigned NOT NULL,
  PRIMARY KEY (`post_id`),
  KEY `post_type` (`post_type`),
  KEY `user_id` (`user_id`),
  KEY `posted_ts` (`posted_ts`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(250) NOT NULL,
  `steakonions` varchar(250) NOT NULL,
  `userlevel` tinyint(3) unsigned NOT NULL DEFAULT '6',
  `last_activity_ts` int(10) unsigned NOT NULL,
  `tsc` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `session_key` varchar(255) NOT NULL,
  `session_secret` varchar(255) NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `expires` int(11) NOT NULL,
  `ts` int(11) NOT NULL,
  PRIMARY KEY (`session_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
