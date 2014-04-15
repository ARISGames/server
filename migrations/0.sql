-- MySQL dump 10.13  Distrib 5.1.60, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: server
-- ------------------------------------------------------
-- Server version	5.1.60-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `ar_media`
--

DROP TABLE IF EXISTS `ar_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_media` (
  `ar_media_id` int(11) NOT NULL,
  `media_type` enum('IMAGE','VIDEO','MODEL') DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `video_autoplay` int(11) DEFAULT NULL,
  `video_loop` int(11) DEFAULT NULL,
  PRIMARY KEY (`ar_media_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ar_targets`
--

DROP TABLE IF EXISTS `ar_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_targets` (
  `ar_target_id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `target_type` enum('FRAME_MARKER','IMAGE_TRACKER','MULTI_TARGET') DEFAULT NULL,
  `frame_marker_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ar_target_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ar_targets_to_media`
--

DROP TABLE IF EXISTS `ar_targets_to_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_targets_to_media` (
  `ar_target_id` int(11) NOT NULL,
  `ar_media_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aug_bubble_media`
--

DROP TABLE IF EXISTS `aug_bubble_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aug_bubble_media` (
  `aug_bubble_id` int(10) unsigned NOT NULL,
  `media_id` int(10) unsigned NOT NULL,
  `text` tinytext NOT NULL,
  `index` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`aug_bubble_id`,`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aug_bubbles`
--

DROP TABLE IF EXISTS `aug_bubbles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aug_bubbles` (
  `aug_bubble_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `icon_media_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`aug_bubble_id`)
) ENGINE=InnoDB AUTO_INCREMENT=402 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `editors`
--

DROP TABLE IF EXISTS `editors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `editors` (
  `editor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `super_admin` enum('0','1') NOT NULL DEFAULT '0',
  `comments` tinytext NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `read_write_token` varchar(64) NOT NULL,
  PRIMARY KEY (`editor_id`),
  UNIQUE KEY `unique_name` (`name`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `created` (`created`)
) ENGINE=InnoDB AUTO_INCREMENT=2142 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `folder_contents`
--

DROP TABLE IF EXISTS `folder_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `folder_contents` (
  `object_content_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` int(10) NOT NULL DEFAULT '0',
  `game_id` int(11) NOT NULL,
  `content_type` enum('Node','Item','Npc','WebPage','AugBubble','PlayerNote') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Node',
  `content_id` int(10) unsigned NOT NULL DEFAULT '0',
  `previous_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`object_content_id`),
  KEY `game_content` (`game_id`,`content_type`,`content_id`),
  KEY `game_folder` (`game_id`,`folder_id`),
  KEY `game_previous` (`game_id`,`previous_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25835 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `folders`
--

DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `folders` (
  `folder_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `previous_id` int(11) NOT NULL DEFAULT '0',
  `is_open` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`folder_id`),
  KEY `game_parent` (`game_id`,`parent_id`),
  KEY `game_previous` (`game_id`,`previous_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1424 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fountains`
--

DROP TABLE IF EXISTS `fountains`;
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

--
-- Table structure for table `game_comments`
--

DROP TABLE IF EXISTS `game_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
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

--
-- Table structure for table `game_editors`
--

DROP TABLE IF EXISTS `game_editors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_editors` (
  `game_id` int(11) NOT NULL DEFAULT '0',
  `editor_id` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `unique` (`game_id`,`editor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_object_tags`
--

DROP TABLE IF EXISTS `game_object_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_object_tags` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `tag` varchar(32) NOT NULL DEFAULT '',
  `media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `use_for_sort` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_tab_data`
--

DROP TABLE IF EXISTS `game_tab_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_tab_data` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `tab` enum('GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME') COLLATE utf8_unicode_ci NOT NULL,
  `tab_index` int(10) unsigned NOT NULL COMMENT '0 for disabled',
  `tab_detail_1` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10739 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `game_tags`
--

DROP TABLE IF EXISTS `game_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `game_tags` (
  `tag_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `tag` varchar(32) NOT NULL DEFAULT 'New Tag',
  `player_created` tinyint(1) NOT NULL DEFAULT '0',
  `media_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tag_id`),
  KEY `game_id` (`game_id`),
  KEY `tag` (`tag`),
  KEY `game_id_tag` (`game_id`,`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=125 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `games`
--

DROP TABLE IF EXISTS `games`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `games` (
  `game_id` int(11) NOT NULL AUTO_INCREMENT,
  `prefix` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `pc_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `allow_player_created_locations` tinyint(1) NOT NULL DEFAULT '0',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delete_player_locations_on_reset` tinyint(1) NOT NULL DEFAULT '0',
  `on_launch_node_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_complete_node_id` int(10) unsigned NOT NULL DEFAULT '0',
  `ready_for_public` tinyint(1) NOT NULL DEFAULT '0',
  `is_locational` tinyint(1) NOT NULL DEFAULT '0',
  `game_icon_media_id` int(11) NOT NULL COMMENT 'NUKE ME',
  `inventory_weight_cap` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `allow_share_note_to_map` tinyint(1) NOT NULL DEFAULT '1',
  `allow_share_note_to_book` tinyint(1) NOT NULL DEFAULT '1',
  `allow_note_comments` tinyint(1) NOT NULL DEFAULT '1',
  `allow_player_tags` tinyint(1) NOT NULL DEFAULT '1',
  `allow_note_likes` tinyint(1) NOT NULL DEFAULT '1',
  `allow_trading` tinyint(1) NOT NULL DEFAULT '1',
  `show_player_location` tinyint(1) NOT NULL DEFAULT '1',
  `use_player_pic` tinyint(1) NOT NULL DEFAULT '0',
  `map_type` enum('STREET','SATELLITE','HYBRID') NOT NULL DEFAULT 'STREET',
  `full_quick_travel` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `offline` tinyint(1) NOT NULL DEFAULT '0',
  `note_title_behavior` enum('NONE','FORCE_OVERWRITE') NOT NULL DEFAULT 'FORCE_OVERWRITE',
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0',
  `zoom_level` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`game_id`),
  KEY `prefixKey` (`prefix`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB AUTO_INCREMENT=3448 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` tinytext NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `items` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `is_attribute` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `dropable` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `destroyable` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `max_qty_in_inventory` int(11) NOT NULL DEFAULT '-1' COMMENT '-1 for infinite, 0 if it can''t be picked up',
  `creator_player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `origin_latitude` double NOT NULL DEFAULT '0',
  `origin_longitude` double NOT NULL DEFAULT '0',
  `origin_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `weight` int(10) unsigned NOT NULL DEFAULT '0',
  `url` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('NORMAL','ATTRIB','URL','NOTE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL',
  `tradeable` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`item_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11140 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `latitude` double NOT NULL DEFAULT '43.0746561',
  `longitude` double NOT NULL DEFAULT '-89.384422',
  `error` double NOT NULL DEFAULT '5',
  `type` enum('Node','Event','Item','Npc','WebPage','AugBubble','PlayerNote') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Node',
  `type_id` int(11) NOT NULL,
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `item_qty` int(11) NOT NULL DEFAULT '0' COMMENT '-1 for infinite. Only effective for items',
  `hidden` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `force_view` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `allow_quick_travel` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `wiggle` tinyint(1) NOT NULL DEFAULT '0',
  `show_title` tinyint(1) NOT NULL DEFAULT '0',
  `spawnstamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`location_id`),
  KEY `game_latitude` (`game_id`,`latitude`),
  KEY `game_longitude` (`game_id`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=22605 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `media_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_icon` tinyint(1) NOT NULL DEFAULT '0',
  `display_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`media_id`,`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=127601 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nodes`
--

DROP TABLE IF EXISTS `nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodes` (
  `node_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `opt1_text` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt1_node_id` int(11) unsigned NOT NULL DEFAULT '0',
  `opt2_text` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt2_node_id` int(11) unsigned NOT NULL DEFAULT '0',
  `opt3_text` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `opt3_node_id` int(11) unsigned NOT NULL DEFAULT '0',
  `require_answer_incorrect_node_id` int(11) unsigned NOT NULL DEFAULT '0',
  `require_answer_string` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `require_answer_correct_node_id` int(10) unsigned NOT NULL DEFAULT '0',
  `media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`node_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17061 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_content`
--

DROP TABLE IF EXISTS `note_content`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_content` (
  `note_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `type` enum('TEXT','MEDIA','PHOTO','VIDEO','AUDIO') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'MEDIA',
  `text` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `sort_index` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `note_id` (`note_id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1783 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_flags`
--

DROP TABLE IF EXISTS `note_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_flags` (
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `note_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_id`,`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_likes`
--

DROP TABLE IF EXISTS `note_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_likes` (
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `note_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`player_id`,`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_shares`
--

DROP TABLE IF EXISTS `note_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_shares` (
  `share_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `note_id` int(10) unsigned NOT NULL DEFAULT '0',
  `share_type` enum('FACEBOOK','TWITTER','PINTEREST','EMAIL') COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`share_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `note_tags`
--

DROP TABLE IF EXISTS `note_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_tags` (
  `note_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`note_id`,`tag_id`),
  KEY `tag_id` (`tag_id`),
  KEY `note_id` (`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `game_id` int(10) unsigned NOT NULL,
  `note_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `parent_note_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sort_index` int(11) NOT NULL DEFAULT '0',
  `public_to_notebook` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `public_to_map` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `incomplete` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4020 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `npc_conversations`
--

DROP TABLE IF EXISTS `npc_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npc_conversations` (
  `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
  `npc_id` int(10) unsigned NOT NULL DEFAULT '0',
  `node_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(11) NOT NULL,
  `text` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `sort_index` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`conversation_id`),
  KEY `game_npc_node` (`game_id`,`npc_id`,`node_id`),
  KEY `game_node` (`game_id`,`node_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9946 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `npcs`
--

DROP TABLE IF EXISTS `npcs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `npcs` (
  `npc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `closing` text COLLATE utf8_unicode_ci NOT NULL,
  `media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`npc_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5652 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `object_tags`
--

DROP TABLE IF EXISTS `object_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `object_tags` (
  `object_type` enum('ITEM') NOT NULL DEFAULT 'ITEM',
  `object_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`object_type`,`object_id`,`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `overlays`
--

DROP TABLE IF EXISTS `overlays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overlays` (
  `overlay_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `media_id` int(11) unsigned NOT NULL,
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

--
-- Table structure for table `player_group`
--

DROP TABLE IF EXISTS `player_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_group` (
  `player_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  PRIMARY KEY (`player_id`,`game_id`),
  UNIQUE KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `player_items`
--

DROP TABLE IF EXISTS `player_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `game_id` int(11) NOT NULL,
  `item_id` int(11) unsigned NOT NULL DEFAULT '0',
  `qty` int(11) NOT NULL DEFAULT '0',
  `viewed` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `game_player_item` (`game_id`,`player_id`,`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14748 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `player_log`
--

DROP TABLE IF EXISTS `player_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event_type` enum('LOGIN','MOVE','PICKUP_ITEM','DROP_ITEM','DROP_NOTE','DESTROY_ITEM','VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_WEBPAGE','VIEW_AUGBUBBLE','VIEW_MAP','VIEW_QUESTS','VIEW_INVENTORY','ENTER_QRCODE','UPLOAD_MEDIA_ITEM','UPLOAD_MEDIA_ITEM_IMAGE','UPLOAD_MEDIA_ITEM_AUDIO','UPLOAD_MEDIA_ITEM_VIDEO','RECEIVE_WEBHOOK','SEND_WEBHOOK','COMPLETE_QUEST','CREATE_NOTE','GET_NOTE','GIVE_NOTE_LIKE','GET_NOTE_LIKE','GIVE_NOTE_COMMENT','GET_NOTE_COMMENT') COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_1` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_2` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_3` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`),
  KEY `timestamp` (`timestamp`),
  KEY `event` (`event_type`,`event_detail_1`,`event_detail_2`,`event_detail_3`),
  KEY `game_id` (`game_id`),
  KEY `check_for_log` (`player_id`,`game_id`,`event_type`,`event_detail_1`,`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2203004 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `player_state_changes`
--

DROP TABLE IF EXISTS `player_state_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `player_state_changes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `event_type` enum('VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_WEBPAGE','VIEW_AUGBUBBLE','RECEIVE_WEBHOOK') COLLATE utf8_unicode_ci NOT NULL,
  `event_detail` int(10) unsigned NOT NULL,
  `action` enum('GIVE_ITEM','TAKE_ITEM') COLLATE utf8_unicode_ci NOT NULL,
  `action_detail` int(10) unsigned NOT NULL,
  `action_amount` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `game_event_lookup` (`game_id`,`event_type`,`event_detail`)
) ENGINE=InnoDB AUTO_INCREMENT=7147 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `players`
--

DROP TABLE IF EXISTS `players`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `players` (
  `player_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(25) DEFAULT NULL,
  `last_name` varchar(25) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `media_id` int(25) unsigned NOT NULL DEFAULT '0',
  `password` varchar(32) DEFAULT NULL,
  `user_name` varchar(30) NOT NULL,
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0',
  `last_game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `show_on_map` tinyint(4) NOT NULL DEFAULT '1',
  `display_name` varchar(32) NOT NULL DEFAULT '',
  `group_name` varchar(32) NOT NULL DEFAULT '',
  `curator` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `facebook_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_id`),
  KEY `position` (`latitude`,`longitude`),
  KEY `last_game_id` (`last_game_id`),
  KEY `created` (`created`)
) ENGINE=InnoDB AUTO_INCREMENT=8913 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `qrcodes`
--

DROP TABLE IF EXISTS `qrcodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qrcodes` (
  `qrcode_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `link_type` enum('Location') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Location',
  `link_id` int(11) NOT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `match_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fail_text` varchar(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'This code doesn''t mean anything right now. You should come back later.',
  PRIMARY KEY (`qrcode_id`),
  KEY `game_link_id` (`game_id`,`link_type`,`link_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20293 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `quests`
--

DROP TABLE IF EXISTS `quests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quests` (
  `quest_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `text_when_complete` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'This is the txt that displays on the completed quests screen',
  `sort_index` int(10) unsigned NOT NULL DEFAULT '0',
  `active_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `full_screen_notify` tinyint(1) NOT NULL DEFAULT '1',
  `active_icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `go_function` enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NONE',
  `description_notification` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `text_when_complete_notification` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `active_notification_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_notification_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_go_function` enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NONE',
  `complete_full_screen_notify` tinyint(1) NOT NULL DEFAULT '1',
  `active_notif_show_dismiss` tinyint(1) NOT NULL DEFAULT '1',
  `complete_notif_show_dismiss` tinyint(1) NOT NULL DEFAULT '1',
  `notif_go_function` enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NONE',
  `complete_notif_go_function` enum('NONE','NEARBY','GPS','QUESTS','INVENTORY','PLAYER','QR','NOTE','PICKGAME','JAVASCRIPT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NONE',
  `complete_requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  `display_requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`quest_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3322 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requirement_and_packages`
--

DROP TABLE IF EXISTS `requirement_and_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_and_packages` (
  `requirement_and_package_id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(32) unsigned NOT NULL,
  `requirement_root_package_id` int(32) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`requirement_and_package_id`),
  KEY `and_requirement_root_package` (`requirement_root_package_id`),
  KEY `and_requirement_game` (`game_id`)
) ENGINE=MyISAM AUTO_INCREMENT=53 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requirement_atoms`
--

DROP TABLE IF EXISTS `requirement_atoms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_atoms` (
  `requirement_atom_id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(32) unsigned NOT NULL,
  `requirement_and_package_id` int(32) unsigned NOT NULL,
  `bool_operator` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `requirement` enum('PLAYER_HAS_ITEM','PLAYER_HAS_TAGGED_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_VIEWED_NPC','PLAYER_VIEWED_WEBPAGE','PLAYER_VIEWED_AUGBUBBLE','PLAYER_HAS_UPLOADED_MEDIA_ITEM','PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE','PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO','PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO','PLAYER_HAS_COMPLETED_QUEST','PLAYER_HAS_REC\nEIVED_INCOMING_WEB_HOOK','PLAYER_HAS_NOTE','PLAYER_HAS_NOTE_WITH_TAG','PLAYER_HAS_NOTE_WITH_LIKES','PLAYER_HAS_NOTE_WITH_COMMENTS','PLAYER_HAS_GIVEN_NOTE_COMMENTS') DEFAULT NULL,
  `content_id` int(32) unsigned NOT NULL DEFAULT '0',
  `qty` int(32) unsigned NOT NULL DEFAULT '0',
  `latitude` double NOT NULL DEFAULT '0',
  `longitude` double NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`requirement_atom_id`),
  KEY `atom_requirement_and_package` (`requirement_and_package_id`),
  KEY `atom_requirement_game` (`game_id`)
) ENGINE=MyISAM AUTO_INCREMENT=93 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requirement_root_packages`
--

DROP TABLE IF EXISTS `requirement_root_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_root_packages` (
  `requirement_root_package_id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(32) unsigned NOT NULL,
  `name` varchar(64) NOT NULL DEFAULT '',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`requirement_root_package_id`),
  KEY `requirement_root_package_game` (`game_id`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `requirements`
--

DROP TABLE IF EXISTS `requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirements` (
  `requirement_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `content_type` enum('Node','QuestDisplay','QuestComplete','Location','OutgoingWebHook','Spawnable','CustomMap','banana') COLLATE utf8_unicode_ci DEFAULT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `requirement` enum('PLAYER_HAS_ITEM','PLAYER_HAS_TAGGED_ITEM','PLAYER_VIEWED_ITEM','PLAYER_VIEWED_NODE','PLAYER_VIEWED_NPC','PLAYER_VIEWED_WEBPAGE','PLAYER_VIEWED_AUGBUBBLE','PLAYER_HAS_UPLOADED_MEDIA_ITEM','PLAYER_HAS_UPLOADED_MEDIA_ITEM_IMAGE','PLAYER_HAS_UPLOADED_MEDIA_ITEM_AUDIO','PLAYER_HAS_UPLOADED_MEDIA_ITEM_VIDEO','PLAYER_HAS_COMPLETED_QUEST','PLAYER_HAS_RECEIVED_INCOMING_WEB_HOOK','PLAYER_HAS_NOTE','PLAYER_HAS_NOTE_WITH_TAG','PLAYER_HAS_NOTE_WITH_LIKES','PLAYER_HAS_NOTE_WITH_COMMENTS','PLAYER_HAS_GIVEN_NOTE_COMMENTS') COLLATE utf8_unicode_ci DEFAULT NULL,
  `boolean_operator` enum('AND','OR') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'AND',
  `not_operator` enum('DO','NOT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'DO',
  `group_operator` enum('SELF','GROUP') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'SELF',
  `requirement_detail_1` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `requirement_detail_2` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `requirement_detail_3` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `requirement_detail_4` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`requirement_id`),
  KEY `game_content_index` (`game_id`,`content_type`,`content_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29796 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spawnables`
--

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

--
-- Table structure for table `web_hooks`
--

DROP TABLE IF EXISTS `web_hooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_hooks` (
  `web_hook_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `url` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `incoming` tinyint(1) unsigned NOT NULL,
  `requirement_package_id` int(32) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`web_hook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=296 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `web_pages`
--

DROP TABLE IF EXISTS `web_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_pages` (
  `web_page_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(10) unsigned NOT NULL,
  `icon_media_id` int(10) unsigned NOT NULL DEFAULT '4',
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `url` tinytext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`web_page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=771 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-04-14 13:20:36
