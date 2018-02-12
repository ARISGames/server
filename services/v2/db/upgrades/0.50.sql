ALTER TABLE quests CHANGE quest_type quest_type enum('QUEST','CATEGORY', 'COMPOUND') NOT NULL DEFAULT 'QUEST';
ALTER TABLE quests ADD prompt varchar(255) DEFAULT '' AFTER description;
