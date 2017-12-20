ALTER TABLE quests ADD `quest_type` enum('QUEST','CATEGORY') NOT NULL DEFAULT 'QUEST' AFTER `description`;
ALTER TABLE quests ADD `parent_quest_id` int(32) unsigned NOT NULL DEFAULT '0' AFTER `sort_index`;
