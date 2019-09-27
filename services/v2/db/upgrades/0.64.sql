CREATE TABLE `field_guides` (`field_guide_id` int(32) unsigned NOT NULL AUTO_INCREMENT, `game_id` int(32) unsigned NOT NULL, `field_id` int(32) unsigned NOT NULL, PRIMARY KEY (`field_guide_id`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
ALTER TABLE `fields` ADD `field_guide_id` int(32) unsigned AFTER `max_color`;
ALTER TABLE `field_options` ADD `remnant_id` int(32) unsigned AFTER `color`;
