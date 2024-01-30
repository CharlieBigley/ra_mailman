# 8 files in total
# 31/05/23 created
# 09/06/23 mail_access
# 20/06/23 subscriptions / reminder_sent
# 01/08/23 remove category_id from mail_lists and mailshots, defauly group_primary to Null
# 08/08/23 delete record_type from mailshots
# 11/08/23 Mailshots: date_sent & processing_started to DATETIME
# 06/09/23 include logfile 14/11/23 CB remove reference to author_id
# 14/11/23 remove mailshots/author_id
CREATE TABLE IF NOT EXISTS `#__ra_mail_access` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `name` varchar(25) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__ra_mail_access` (`name`) VALUES
    ('Subscriber'),
    ('Author'),
    ('Owner');
# ------------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `#__ra_mail_lists` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`state` INT NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`group_code` VARCHAR(4) NOT NULL,
        `group_primary` VARCHAR(4) DEFAULT NULL, 
	`owner_id` INT NOT NULL,
	`record_type` VARCHAR(1) NOT NULL,
	`home_group_only` INT NOT NULL,
        `chat_list` VARCHAR(1)  NOT NULL  DEFAULT "0",
	`footer` MEDIUMTEXT NOT NULL,
# Following two fields probably not required
	`ordering` INT NULL,
        `checked_out_time` DATETIME NULL DEFAULT NULL,

 	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   
	`created_by` INT NULL DEFAULT "0",
 	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by` INT NULL DEFAULT "0",
    PRIMARY KEY (`id`),
    INDEX idx_owner_id(owner_id),
    INDEX idx_created_by(created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_mail_methods` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `name` varchar(25) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT INTO `#__ra_mail_methods` (`name`) VALUES
('Self registered'),
('Administrator'),
('Corporate feed'),
('MailChimp'),
('CSV'),
('Email');
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_mail_recipients` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`mailshot_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`email` VARCHAR(100) NOT NULL,
	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `created_by` INT NULL DEFAULT "0",
	`ip_address` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX idx_user_id(user_id),
    INDEX idx_mailshot_id(mailshot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_mail_shots` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`mail_list_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `body` longtext NOT NULL,
        `final_message` longtext,
        `attachment` VARCHAR(255) NOT NULL DEFAULT '',
        `processing_started` DATETIME DEFAULT NULL,
        `date_sent` DATETIME DEFAULT NULL,
        `state` TINYINT NOT NULL,
# Following field probably not required
	`ordering` INT NOT NULL,

 	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   
	`created_by` INT NULL DEFAULT "0",
 	`modified` DATETIME NULL DEFAULT NULL,
	`modified_by`INT NULL DEFAULT "0",
    PRIMARY KEY (`id`),
    INDEX idx_mail_list_id(mail_list_id),
    INDEX idx_created_by(created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_mail_subscriptions` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`list_id` INT NOT NULL,
	`user_id` INT NOT NULL,
	`record_type` INT NOT NULL,
        `method_id` INT NOT NULL,
        `state` TINYINT NOT NULL,
        `ip_address` VARCHAR(50) NOT NULL,
	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  
        `created_by` INT NULL DEFAULT "0",
	`modified` DATETIME NULL DEFAULT NULL,
        `modified_by` INT NULL DEFAULT "0",
	`expiry_date` DATE NULL,
        `reminder_sent` DATETIME,
    PRIMARY KEY (`id`),
    INDEX idx_user_id(user_id),
    INDEX idx_list_id(list_id),
    INDEX idx_method_id(method_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_mail_subscriptions_audit` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`object_id` INT NOT NULL,
	`field_name` VARCHAR(50) NOT NULL,
	`old_value`VARCHAR(50) NOT NULL,
        `new_value` VARCHAR(50) NOT NULL,
	`ip_address` VARCHAR(50) NOT NULL,
        `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        `created_by` INT NULL DEFAULT "0",
    PRIMARY KEY (`id`),
    INDEX idx_object_id(object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_profiles` (
        `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `home_group` VARCHAR(255)  NOT NULL ,
        `preferred_name` VARCHAR(60)  NOT NULL ,
        `groups_to_follow` VARCHAR(255)  NOT NULL ,
        `privacy_level` VARCHAR(1)  NOT NULL  DEFAULT "3",
        `mobile` VARCHAR(100)  NULL  DEFAULT "",
        `contactviaemail` VARCHAR(1)  NULL  DEFAULT "1",
        `contactviatextmessage` VARCHAR(1)  NULL  DEFAULT "0",
        `acknowledge_follow` VARCHAR(1)  NULL  DEFAULT "0",
        `notify_joiners` VARCHAR(255)  NULL  DEFAULT "1",
        `min_miles` VARCHAR(2)  NULL  DEFAULT "0",
        `max_miles` VARCHAR(2)  NULL  DEFAULT "0",
        `max_radius` VARCHAR(3)  NULL  DEFAULT "30",
        `state` TINYINT(1) NULL DEFAULT 1,
        `created` DATETIME NULL DEFAULT NULL,
        `created_by` INT(11) NULL DEFAULT 0,
        `modified` DATETIME NULL DEFAULT NULL,
        `modified_by` INT(11)  NULL  DEFAULT 0,
    PRIMARY KEY (`id`),
    INDEX idx_home_group(home_group)
) DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
# Logfile is required for the CLI program ra_renewals
CREATE TABLE IF NOT EXISTS `#__ra_logfile` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `log_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `record_type` char(2) NOT NULL,
  `ref` varchar(10) DEFAULT NULL,
  `message` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `#__ra_booking_rates` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`hours` DECIMAL(4,2) NOT NULL,
	`total_charge` DECIMAL(5,2) NOT NULL,
        `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        `created_by` INT NULL DEFAULT "0",
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
# ------------------------------------------------------------------------------
# INSERT INTO `#__ra_booking_rates` (`id`, `hours`, `total_charge`, `created`, `created_by`) 
# VALUES (NULL, '3', '50', CURRENT_TIMESTAMP, '0'), 
# (NULL, '4', '68', CURRENT_TIMESTAMP, '0');