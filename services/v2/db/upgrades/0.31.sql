CREATE TABLE `colors` (
  `colors_id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `tag_1` varchar(255) DEFAULT '',
  `tag_2` varchar(255) DEFAULT '',
  `tag_3` varchar(255) DEFAULT '',
  `tag_4` varchar(255) DEFAULT '',
  `tag_5` varchar(255) DEFAULT '',
  PRIMARY KEY (`colors_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `games` ADD `colors_id` INT(32)  UNSIGNED  NULL  DEFAULT NULL  AFTER `last_active`;
