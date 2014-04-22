/*
CREATE THE DATABASE
*/
DROP USER 'tmp_user'@'127.0.0.1';
CREATE USER 'tmp_user'@'127.0.0.1' IDENTIFIED BY 'tmp_pass';
DROP DATABASE IF EXISTS tmp_db;
CREATE DATABASE tmp_db;
GRANT ALL ON tmp_db.* TO 'tmp_user'@'127.0.0.1';
USE tmp_db;

DROP TABLE IF EXISTS aris_migrations;
CREATE TABLE aris_migrations (
version_major INT(32) UNSIGNED NOT NULL,
version_minor INT(32) UNSIGNED NOT NULL,
timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (version_major, version_minor)
);

/*
GAME DATA
*/

DROP TABLE IF EXISTS games;
CREATE TABLE games (
game_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
/* tab data */
    /* map */
map_type enum('STREET','SATELLITE','HYBRID') NOT NULL DEFAULT 'STREET',
latitude DOUBLE NOT NULL DEFAULT 0.0,
longitude DOUBLE NOT NULL DEFAULT 0.0,
zoom_level DOUBLE NOT NULL DEFAULT 0.0,
show_player_location TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
full_quick_travel TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    /* notes */
allow_note_comments TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
allow_note_player_tags TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
allow_note_likes TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    /* inventory */
inventory_weight_cap INT(32) NOT NULL DEFAULT -1,

ready_for_public TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS game_tab_data;
CREATE TABLE game_tab_data (
  game_id INT(32) UNSIGNED NOT NULL,
  tab ENUM('GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME') COLLATE utf8_unicode_ci NOT NULL,
  tab_index INT(32) NOT NULL,
  tab_detail_1 INT(32) DEFAULT '0',
  PRIMARY KEY (game_id, tab)
);

DROP TABLE IF EXISTS objects;
CREATE TABLE objects (
object_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
object_type ENUM('ITEM','NPC','PLAQUE','WEB_PAGE'),
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX object_game ON objects(game_id);

DROP TABLE IF EXISTS items;
CREATE TABLE items (
item_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
droppable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
destroyable TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
max_qty_in_inventory INT(32) NOT NULL DEFAULT -1,
weight INT(32) UNSIGNED NOT NULL DEFAULT 0,
url TINYTEXT NOT NULL,
type enum('NORMAL','ATTRIB','URL') NOT NULL DEFAULT 'NORMAL',
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX item_game_id ON items(game_id);

DROP TABLE IF EXISTS npcs;
CREATE TABLE npcs (
npc_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
opening_script_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
closing_script_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX npc_game_id ON npcs(game_id);

DROP TABLE IF EXISTS npc_scripts;
CREATE TABLE npc_scripts (
npc_script_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
npc_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
text TEXT NOT NULL,
sort_index INT(32) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX npc_script_game_id ON npc_scripts(game_id);
CREATE INDEX npc_script_npc_id ON npc_scripts(npc_id);

DROP TABLE IF EXISTS plaques;
CREATE TABLE plaques (
plaque_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX plaque_game_id ON plaques(game_id);

DROP TABLE IF EXISTS web_pages;
CREATE TABLE web_pages (
web_page_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL,
icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
url TINYTEXT NOT NULL,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX web_page_game_id ON web_pages(game_id);

DROP TABLE IF EXISTS notes;
CREATE TABLE notes (
note_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
user_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX note_game_id ON notes(game_id);
CREATE INDEX note_user_id ON notes(user_id);

CREATE TABLE quests (
quest_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
description TEXT NOT NULL,
active_icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
active_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
active_description TEXT NOT NULL,
active_notification_type ENUM('NONE','FULL_SCREEN','DROP_DOWN'),
active_function ENUM('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') NOT NULL DEFAULT 'NONE',
active_requirement_package_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
complete_icon_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
complete_media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
complete_description TEXT NOT NULL,
complete_notification_type ENUM('NONE','FULL_SCREEN','DROP_DOWN'),
complete_function ENUM('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') NOT NULL DEFAULT 'NONE',
complete_requirement_package_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
sort_index INT(32) UNSIGNED NOT NULL DEFAULT '0',
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX quest_game_id ON quests(game_id);

DROP TABLE IF EXISTS media;
CREATE TABLE media (
media_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
file_folder VARCHAR(255) NOT NULL DEFAULT "",
file_name VARCHAR(255) NOT NULL DEFAULT "",
display_name VARCHAR(255) DEFAULT ''
);
CREATE INDEX media_game_id ON media(game_id);

DROP TABLE IF EXISTS scenes;
CREATE TABLE scenes (
scene_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX scene_game ON scenes(game_id);

DROP TABLE IF EXISTS instances;
CREATE TABLE instances (
instance_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
object_id INT(32) UNSIGNED NOT NULL,
spawnable_id INT(32) UNSIGNED NOT NULL,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX instance_object ON instances(object_id);
CREATE INDEX instance_game ON instances(game_id);

DROP TABLE IF EXISTS triggers;
CREATE TABLE triggers (
trigger_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
instance_id INT(32) UNSIGNED NOT NULL,
scene_id INT(32) UNSIGNED NOT NULL,
requirement_root_package_id INT(32) UNSIGNED NOT NULL,
type ENUM('IMMEDIATE','LOCATION','AUTO_LOCATION','HIDDEN_AUTO_LOCATION','QR'),
latitude DOUBLE NOT NULL DEFAULT 0.0,
longitude DOUBLE NOT NULL DEFAULT 0.0,
distance INT(32) NOT NULL DEFAULT 0,
wiggle TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
show_title TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
code VARCHAR(255) NOT NULL DEFAULT "",
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX trigger_instance ON triggers(instance_id);
CREATE INDEX trigger_scene ON triggers(scene_id);
CREATE INDEX trigger_game ON triggers(game_id);

DROP TABLE IF EXISTS requirement_root_packages;
CREATE TABLE requirement_root_packages (
requirement_root_package_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX requirement_root_package_game ON requirement_root_packages(game_id);

DROP TABLE IF EXISTS requirement_and_packages;
CREATE TABLE requirement_and_packages (
requirement_and_package_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
requirement_root_package_id INT(32) UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL DEFAULT "",
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX and_requirement_root_package ON requirement_and_packages(requirement_root_package_id);
CREATE INDEX and_requirement_game ON requirement_and_packages(game_id);

DROP TABLE IF EXISTS requirement_atoms;
CREATE TABLE requirement_atoms (
requirement_atom_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
game_id INT(32) UNSIGNED NOT NULL,
requirement_and_package_id INT(32) UNSIGNED NOT NULL,
bool_operator TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
requirement ENUM('PLAYER_HAS_ITEM','PLAYER_HAS_TAGGED_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_VIEWED_NPC','PLAYER_VIEWED_WEB_PAGE','PLAYER_VIEWED_AUGBUBBLE','PLAYER_HAS_UPLOADED_MEDIA_ITEM','PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE','PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO','PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO','PLAYER_HAS_COMPLETED_QUEST','PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK','PLAYER_HAS_NOTE','PLAYER_HAS_NOTE_WITH_TAG','PLAYER_HAS_NOTE_WITH_LIKES','PLAYER_HAS_NOTE_WITH_COMMENTS','PLAYER_HAS_GIVEN_NOTE_COMMENTS'),
content_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
distance INT(32) NOT NULL DEFAULT 0,
qty INT(32) UNSIGNED NOT NULL DEFAULT 0,
latitude DOUBLE NOT NULL DEFAULT 0.0,
longitude DOUBLE NOT NULL DEFAULT 0.0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX atom_requirement_and_package ON requirement_atoms(requirement_and_package_id);
CREATE INDEX atom_requirement_game ON requirement_atoms(game_id);


/*
USER DATA
*/

DROP TABLE IF EXISTS users;
CREATE TABLE users (
user_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
user_name VARCHAR(255) NOT NULL DEFAULT "",
display_name VARCHAR(255) NOT NULL DEFAULT "",
email VARCHAR(255) NOT NULL DEFAULT "",
media_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
hash VARCHAR(255) NOT NULL,
salt VARCHAR(255) NOT NULL,
read_key VARCHAR(255) NOT NULL,
write_key VARCHAR(255) NOT NULL,
read_write_key VARCHAR(255) NOT NULL,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX user_user_name ON users(user_name);
CREATE INDEX user_email ON users(email);

DROP TABLE IF EXISTS user_games;
CREATE TABLE user_games (
game_id INT(32) UNSIGNED NOT NULL DEFAULT '0',
user_id INT(32) UNSIGNED NOT NULL DEFAULT '0',
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (user_id, game_id)
);
CREATE INDEX user_game_game_id ON user_games(game_id);

DROP TABLE IF EXISTS user_game_scenes;
CREATE TABLE user_game_scenes (
user_id INT(32) UNSIGNED NOT NULL,
game_id INT(32) UNSIGNED NOT NULL,
scene_id INT(32) UNSIGNED NOT NULL,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (user_id, game_id)
);

DROP TABLE IF EXISTS user_log;
CREATE TABLE user_log (
user_log_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
user_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
game_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
event_type ENUM('LOGIN','MOVE','PICKUP_ITEM','DROP_ITEM','DROP_NOTE','DESTROY_ITEM','VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_WEB_PAGE','VIEW_AUGBUBBLE','VIEW_MAP','VIEW_QUESTS','VIEW_INVENTORY','ENTER_QRCODE','UPLOAD_MEDIA_ITEM','UPLOAD_MEDIA_ITEM_IMAGE','UPLOAD_MEDIA_ITEM_AUDIO','UPLOAD_MEDIA_ITEM_VIDEO','RECEIVE_WEBHOOK','SEND_WEBHOOK','COMPLETE_QUEST','CREATE_NOTE','GIVE_NOTE_LIKE','GET_NOTE_LIKE','GIVE_NOTE_COMMENT','GET_NOTE_COMMENT'),
content_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
qty INT(32) UNSIGNED NOT NULL DEFAULT 0,
latitude DOUBLE NOT NULL DEFAULT 0.0,
longitude DOUBLE NOT NULL DEFAULT 0.0,
deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT CURRENT_TIMESTAMP /* no 'last active', as rows are immutable */
);
CREATE INDEX user_log_check ON user_log(user_id, game_id, event_type, deleted);
CREATE INDEX user_log_user_id ON user_log(user_id, created);
CREATE INDEX user_log_game_id ON user_log(game_id, created);

/* Inventory, attributes, etc... */
DROP TABLE IF EXISTS user_instances;
CREATE TABLE user_instances (
game_id int(11) NOT NULL,
user_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
instance_id INT(32) UNSIGNED NOT NULL DEFAULT 0,
created TIMESTAMP DEFAULT '0000-00-00 00:00:00',
last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
PRIMARY KEY (user_id, instance_id)
);
CREATE INDEX user_instance_user_game_id ON user_instances(user_id, game_id);





DROP TABLE IF EXISTS `spawnables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spawnables` (
  `spawnable_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `type` enum('Node','Item','Npc','WebPage','AugBubble','PlayerNote') NOT NULL,
  `type_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '1',
  `max_area` int(11) NOT NULL,
  `amount_restriction` enum('PER_PLAYER','TOTAL') NOT NULL DEFAULT 'PER_PLAYER',
  `location_bound_type` enum('PLAYER','LOCATION') NOT NULL DEFAULT 'PLAYER',
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0',
  `spawn_probability` double NOT NULL DEFAULT '1',
  `spawn_rate` int(11) NOT NULL DEFAULT '10',
  `delete_when_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `time_to_live` int(11) NOT NULL DEFAULT '100',
  `last_spawned` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `error_range` int(11) NOT NULL DEFAULT '10',
  `force_view` tinyint(1) NOT NULL DEFAULT '0',
  `hidden` tinyint(1) NOT NULL DEFAULT '0',
  `allow_quick_travel` tinyint(1) NOT NULL DEFAULT '0',
  `wiggle` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `location_name` tinytext NOT NULL,
  `show_title` tinyint(1) NOT NULL DEFAULT '0',
  `min_area` int(11) NOT NULL,
  `requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`spawnable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=80 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS fountains;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fountains` (
  `fountain_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `type` enum('Location','Spawnable') NOT NULL,
  `location_id` int(11) NOT NULL,
  `spawn_probability` double NOT NULL,
  `spawn_rate` int(11) NOT NULL,
  `max_amount` int(11) NOT NULL,
  `last_spawned` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`fountain_id`)
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `game_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_comments` (
  `id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT(32) UNSIGNED NOT NULL,
  `player_id` INT(32) UNSIGNED NOT NULL,
  `time_stamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rating` int(11) NOT NULL,
  `comment` tinytext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` tinytext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `player_id` (`player_id`),
  KEY `time_stamp` (`time_stamp`)
) ENGINE=InnoDB AUTO_INCREMENT=276 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `game_object_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_object_tags` (
  `tag_id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT(32) UNSIGNED NOT NULL,
  `tag` varchar(32) NOT NULL DEFAULT '',
  `media_id` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  `use_for_sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `game_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_tags` (
  `tag_id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT(32) UNSIGNED NOT NULL,
  `tag` varchar(32) NOT NULL DEFAULT 'New Tag',
  `player_created` tinyint(1) NOT NULL DEFAULT '0',
  `media_id` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`),
  KEY `game_id` (`game_id`),
  KEY `tag` (`tag`),
  KEY `game_id_tag` (`game_id`,`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `note_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_content` (
  `note_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `type` enum('TEXT','MEDIA','PHOTO','VIDEO','AUDIO') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'MEDIA',
  `text` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `sort_index` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  `game_id` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  `content_id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `note_id` (`note_id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1783 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `note_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_likes` (
  `player_id` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  `note_id` INT(32) UNSIGNED NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_id`,`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `note_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_tags` (
  `note_id` INT(32) UNSIGNED NOT NULL,
  `tag_id` INT(32) UNSIGNED NOT NULL,
  PRIMARY KEY (`note_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  KEY `note_id` (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `object_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `object_tags` (
  `object_type` enum('ITEM') NOT NULL DEFAULT 'ITEM',
  `object_id` INT(32) UNSIGNED NOT NULL,
  `tag_id` INT(32) UNSIGNED NOT NULL,
  PRIMARY KEY (`object_type`,`object_id`,`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

DROP TABLE IF EXISTS `overlays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overlays` (
  `overlay_id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT(32) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `media_id` INT(32) UNSIGNED NOT NULL,
  `top_left_latitude` double NOT NULL DEFAULT '0',
  `top_left_longitude` double NOT NULL DEFAULT '0',
  `top_right_latitude` double NOT NULL DEFAULT '0',
  `top_right_longitude` double NOT NULL DEFAULT '0',
  `bottom_left_latitude` double NOT NULL DEFAULT '0',
  `bottom_left_longitude` double NOT NULL DEFAULT '0',
  `requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`overlay_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


DROP TABLE IF EXISTS `player_state_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_state_changes` (
  `id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `event_type` enum('VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_WEB_PAGE','VIEW_AUGBUBBLE','RECEIVE_WEBHOOK') COLLATE utf8_unicode_ci NOT NULL,
  `event_detail` INT(32) UNSIGNED NOT NULL,
  `action` enum('GIVE_ITEM','TAKE_ITEM') COLLATE utf8_unicode_ci NOT NULL,
  `action_detail` INT(32) UNSIGNED NOT NULL,
  `action_amount` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `game_event_lookup` (`game_id`,`event_type`,`event_detail`)
) ENGINE=InnoDB AUTO_INCREMENT=7147 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `web_hooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_hooks` (
  `web_hook_id` INT(32) UNSIGNED NOT NULL AUTO_INCREMENT,
  `game_id` INT(32) UNSIGNED NOT NULL,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `url` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `incoming` tinyint(1) unsigned NOT NULL,
  `requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`web_hook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
