ALTER TABLE `fields` DROP COLUMN `field_guide_id`;
ALTER TABLE `fields` ADD `quest_id` int(32) unsigned AFTER `max_color`;
