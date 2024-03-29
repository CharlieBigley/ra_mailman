CREATE TABLE IF NOT EXISTS `#__ra_profiles` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`home_group` VARCHAR(255)  NOT NULL ,
`preferred_name` VARCHAR(60)  NOT NULL ,
`groups_to_follow` VARCHAR(255)  NOT NULL ,
`acknowledge_follow` VARCHAR(255)  NULL  DEFAULT "0",
`privacy_level` VARCHAR(255)  NOT NULL  DEFAULT "3",
`mobile` VARCHAR(100)  NULL  DEFAULT "",
`contactviaemail` VARCHAR(255)  NULL  DEFAULT "1",
`min_miles` VARCHAR(2)  NULL  DEFAULT "0",
`contactviatextmessage` VARCHAR(255)  NULL  DEFAULT "0",
`max_miles` VARCHAR(2)  NULL  DEFAULT "0",
`max_radius` VARCHAR(3)  NULL  DEFAULT "30",
`notify_joiners` VARCHAR(255)  NULL  DEFAULT "1",
`state` TINYINT(1)  NULL  DEFAULT 1,
`ordering` INT(11)  NULL  DEFAULT 0,
`created` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified` DATETIME NULL  DEFAULT NULL ,
`modified_by` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`),
 INDEX idx_home_group(home_group)
) DEFAULT COLLATE=utf8mb4_unicode_ci;
