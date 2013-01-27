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
-- Dumping data for table `ar_media`
--

LOCK TABLES `ar_media` WRITE;
/*!40000 ALTER TABLE `ar_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_media` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `ar_targets`
--

LOCK TABLES `ar_targets` WRITE;
/*!40000 ALTER TABLE `ar_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_targets` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `ar_targets_to_media`
--

LOCK TABLES `ar_targets_to_media` WRITE;
/*!40000 ALTER TABLE `ar_targets_to_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `ar_targets_to_media` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `aug_bubble_media`
--

LOCK TABLES `aug_bubble_media` WRITE;
/*!40000 ALTER TABLE `aug_bubble_media` DISABLE KEYS */;
/*!40000 ALTER TABLE `aug_bubble_media` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=395 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aug_bubbles`
--

LOCK TABLES `aug_bubbles` WRITE;
/*!40000 ALTER TABLE `aug_bubbles` DISABLE KEYS */;
/*!40000 ALTER TABLE `aug_bubbles` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`editor_id`),
  UNIQUE KEY `unique_name` (`name`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `created` (`created`)
) ENGINE=InnoDB AUTO_INCREMENT=2138 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `editors`
--

LOCK TABLES `editors` WRITE;
/*!40000 ALTER TABLE `editors` DISABLE KEYS */;
/*!40000 ALTER TABLE `editors` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=22168 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folder_contents`
--

LOCK TABLES `folder_contents` WRITE;
/*!40000 ALTER TABLE `folder_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `folder_contents` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=1409 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `folders`
--

LOCK TABLES `folders` WRITE;
/*!40000 ALTER TABLE `folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `folders` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fountains`
--

LOCK TABLES `fountains` WRITE;
/*!40000 ALTER TABLE `fountains` DISABLE KEYS */;
/*!40000 ALTER TABLE `fountains` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`id`),
  KEY `game_id` (`game_id`),
  KEY `player_id` (`player_id`),
  KEY `time_stamp` (`time_stamp`)
) ENGINE=InnoDB AUTO_INCREMENT=271 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_comments`
--

LOCK TABLES `game_comments` WRITE;
/*!40000 ALTER TABLE `game_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_comments` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `game_editors`
--

LOCK TABLES `game_editors` WRITE;
/*!40000 ALTER TABLE `game_editors` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_editors` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`tag_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_object_tags`
--

LOCK TABLES `game_object_tags` WRITE;
/*!40000 ALTER TABLE `game_object_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_object_tags` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=9659 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_tab_data`
--

LOCK TABLES `game_tab_data` WRITE;
/*!40000 ALTER TABLE `game_tab_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_tab_data` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`tag_id`),
  KEY `game_id` (`game_id`),
  KEY `tag` (`tag`),
  KEY `game_id_tag` (`game_id`,`tag`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `game_tags`
--

LOCK TABLES `game_tags` WRITE;
/*!40000 ALTER TABLE `game_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `game_tags` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`game_id`),
  KEY `prefixKey` (`prefix`),
  KEY `created` (`created`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB AUTO_INCREMENT=3367 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `games`
--

LOCK TABLES `games` WRITE;
/*!40000 ALTER TABLE `games` DISABLE KEYS */;
/*!40000 ALTER TABLE `games` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=10943 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `items`
--

LOCK TABLES `items` WRITE;
/*!40000 ALTER TABLE `items` DISABLE KEYS */;
/*!40000 ALTER TABLE `items` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`location_id`),
  KEY `game_latitude` (`game_id`,`latitude`),
  KEY `game_longitude` (`game_id`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=17924 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=41845 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES (1,0,'Default NPC','0/npc.png',1,''),(1,159,'Dow Intro','159/dow_intro.3gp',0,''),(2,0,'Default Item','0/item.png',1,''),(2,159,'Marchers - Bascom','159/arisd14e93c815b63b505a63acff491e63df.3gp',0,''),(3,0,'Default Plaque','0/plaque.png',1,''),(3,159,'Inside Commerce','159/arisba33739bc4e980f383504b2f912a5e16.3gp',0,''),(4,0,'Default WebPage','0/webpage.png',1,''),(4,159,'Soglin','159/arisd6f246cfa5eb2abc8e0a79e6f2abab47.3gp',0,''),(5,159,'Police Arriving','159/aris3bd3b6a39022002353127054a12cfb7e.3gp',0,''),(6,159,'Violence','159/ViolenceNew300.3gp',0,''),(7,159,'Tear Gas','159/arisa955d00a8c4101732b51e2b412755169.3gp',0,''),(8,159,'Editor','159/arisbe22d15fa63e4d409d0dc83760cb87fa.jpg',0,''),(9,159,'Dow Recruiter','159/arise67889b7a94f3b2f130ec5a50ed7a188.jpg',0,''),(10,159,'Protestor','159/aris3b84abaff6c495b71876d9b1abb5b253.jpg',0,''),(11,159,'Kauffman','159/arise51da5b91ce0da6070c10a29b72b3a62.jpg',0,''),(12,159,'legislator','159/aris444742a0d40421f904b36c2a441f371c.jpg',0,''),(13,159,'bascom marchers','159/arisda9ea7c9b648bfc7a9ebe2b184f435d2.jpg',0,''),(14,159,'flyer','159/flyer.jpg',0,''),(15,159,'faculty document','159/aris9de69c34e3d7cf7b2c1bdea04665f046.png',0,''),(16,159,'Mary','159/aris3e8084b839a94e46ce805a72ba531904.jpg',0,''),(17,159,'Flag','159/aris9c67df7a583d8eb6aab5e66a14016f32.jpeg',0,''),(18,159,'Inside Commerce','159/arisdd8606cfef4d65542257d8414df15206.jpg',0,''),(19,159,'Hanson','159/aris5db8026f5472f4b4ed491f80c0b43c7f.jpg',0,''),(20,159,'Student 1','159/aris4154d3db8fdbbd4e42daa98d35a1c529.jpg',0,''),(21,159,'Student 2','159/aris6ee6f30295577181ef29c5f8c433ce8e.jpg',0,''),(22,159,'student leader','159/aris0dff13769c8a8fa245d6ae60ce2c1039.jpg',0,''),(23,159,'Police Officer','159/aris7848edb1586afab00daaea30bc02a7c9.jpg',0,''),(24,159,'Student 3','159/aris23c018c0e526d6064e75ad8de4c9c5c6.jpg',0,''),(25,159,'Chief Emery','159/arise4ba8e990407de4296557a443c671fff.jpg',0,''),(26,0,'Audio CD','0/audiocd.png',1,''),(26,159,'Outside Commerce','159/aris3ede1b9a81c5f3f4324565af98bc709a.jpg',0,''),(27,0,'Video Camera','0/cam.png',1,''),(27,159,'police enter commerce','159/police-at-library-mall.jpg',0,''),(28,0,'Dialog','0/dialog.png',1,''),(28,159,'Teargas','159/aris05cdf87bd72834e698807059479c662c.jpg',0,''),(29,0,'Disk','0/disk.png',1,''),(29,159,'Flag','159/aris3ff2d2fa32477e08e3073fd17ae38384.jpg',0,''),(30,0,'Earphone','0/earphone.png',1,''),(31,0,'Flag','0/flag.png',1,''),(32,0,'Home','0/home.png',1,''),(33,0,'Person','0/man.png',1,''),(34,0,'Microphone','0/mic.png',1,''),(35,0,'Movie','0/movie.png',1,''),(36,0,'Camera','0/camera.png',1,''),(37,0,'Slapper','0/slapper.png',1,''),(38,0,'TV','0/tv.png',1,''),(39,0,'Volume','0/volume.png',1,''),(40,0,'Backpack','0/backpack.png',0,''),(41,0,'Police Badge','0/badge.png',0,''),(42,0,'Binocs','0/binocs.png',0,''),(43,0,'Box - Blue','0/bluebox.png',0,''),(44,0,'Bonsai Tree','0/bonsia.png',0,''),(45,0,'Bowl - Gold','0/bowl.png',0,''),(46,0,'Box','0/box.png',0,''),(47,0,'Box - Active','0/boxactive.png',0,''),(48,0,'Boxes on a Shelf','0/boxesonshelf.png',0,''),(49,0,'Bracelet','0/bracelet.png',0,''),(50,0,'Briefcase','0/breifcase.png',0,''),(51,0,'Camera - Nikon','0/camera_nikon.png',0,''),(52,0,'Camera - Film','0/filmcamera.png',0,''),(53,0,'Coke Bottle','0/coke.png',0,''),(54,0,'Compass','0/compass.png',0,''),(55,0,'Film','0/film.png',0,''),(56,0,'Film Reel','0/filmreel.png',0,''),(57,0,'First Aid','0/firstaid.png',0,''),(58,0,'Box - Fragile','0/fragilebox.png',0,''),(59,0,'Gameboy','0/gameboy.png',0,''),(60,0,'Handbag','0/handbag.png',0,''),(61,0,'iPad','0/ipad.png',0,''),(62,0,'iPhone','0/iPhone.png',0,''),(63,0,'Key','0/key.jpg',0,''),(64,0,'Keys','0/keys.png',0,''),(65,0,'Lipstick','0/lipstick.png',0,''),(66,0,'Lollipop','0/lollipop.png',0,''),(67,0,'Love Letters','0/loveletters.png',0,''),(68,0,'Mail','0/mail.png',0,''),(69,0,'Notebook - Moleskine','0/moleskine.png',0,''),(70,0,'Money','0/money.png',0,''),(71,0,'Notepad','0/notepad.png',0,''),(72,0,'Cell Phone - Old','0/oldcellphone.png',0,''),(73,0,'Coin - Old','0/oldcoin.png',0,''),(74,0,'Folder - Old','0/oldfolder.png',0,''),(75,0,'Box - Open','0/openbox.png',0,''),(76,0,'Parchment','0/parchment.png',0,''),(77,0,'Pen','0/pen.png',0,''),(78,0,'Pendant','0/pendant.png',0,''),(79,0,'Perfume','0/perfume.png',0,''),(80,0,'Picture','0/picture.png',0,''),(81,0,'Purse','0/purse.png',0,''),(82,0,'Wallet','0/pursewallet.png',0,''),(83,0,'Radio','0/radio.png',0,''),(84,0,'Radioactive','0/radioactive.png',0,''),(85,0,'Basket - Red','0/redbasket.png',0,''),(86,0,'Sports Bag','0/sportsbag.png',0,''),(87,0,'Suitcase','0/suitcase.png',0,''),(88,0,'Taqueria','0/taqueria.png',0,''),(89,0,'Trash - Empty','0/trash.png',0,''),(90,0,'Trash - Full','0/trashfull.png',0,''),(91,0,'Umbrella','0/umbrella.png',0,'')/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=16307 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nodes`
--

LOCK TABLES `nodes` WRITE;
/*!40000 ALTER TABLE `nodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `nodes` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`content_id`),
  KEY `note_id` (`note_id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB AUTO_INCREMENT=317 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_content`
--

LOCK TABLES `note_content` WRITE;
/*!40000 ALTER TABLE `note_content` DISABLE KEYS */;
/*!40000 ALTER TABLE `note_content` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `note_likes`
--

DROP TABLE IF EXISTS `note_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `note_likes` (
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `note_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_id`,`note_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `note_likes`
--

LOCK TABLES `note_likes` WRITE;
/*!40000 ALTER TABLE `note_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `note_likes` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `note_tags`
--

LOCK TABLES `note_tags` WRITE;
/*!40000 ALTER TABLE `note_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `note_tags` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=453 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notes`
--

LOCK TABLES `notes` WRITE;
/*!40000 ALTER TABLE `notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `notes` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=9440 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `npc_conversations`
--

LOCK TABLES `npc_conversations` WRITE;
/*!40000 ALTER TABLE `npc_conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `npc_conversations` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=5510 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `npcs`
--

LOCK TABLES `npcs` WRITE;
/*!40000 ALTER TABLE `npcs` DISABLE KEYS */;
/*!40000 ALTER TABLE `npcs` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `object_tags`
--

LOCK TABLES `object_tags` WRITE;
/*!40000 ALTER TABLE `object_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `object_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overlay_tiles`
--

DROP TABLE IF EXISTS `overlay_tiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overlay_tiles` (
  `overlay_id` int(11) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `zoom` int(11) DEFAULT NULL,
  `x` int(11) DEFAULT NULL,
  `x_max` int(11) DEFAULT NULL,
  `y` int(11) DEFAULT NULL,
  `y_max` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overlay_tiles`
--

LOCK TABLES `overlay_tiles` WRITE;
/*!40000 ALTER TABLE `overlay_tiles` DISABLE KEYS */;
INSERT INTO `overlay_tiles` VALUES (0,22002,14,4122,NULL,10367,NULL),(0,22003,14,4122,NULL,10368,NULL),(0,22004,13,2061,NULL,5183,NULL),(0,22005,13,2061,NULL,5184,NULL),(1,38012,13,4130,NULL,6055,NULL),(1,38011,13,4130,NULL,6054,NULL),(1,38010,14,8261,NULL,12109,NULL),(1,38009,14,8261,NULL,12108,NULL),(1,38008,14,8261,NULL,12110,NULL),(1,38007,14,8260,NULL,12109,NULL),(1,38006,14,8260,NULL,12108,NULL),(1,38005,14,8260,NULL,12110,NULL),(27,28049,14,8260,NULL,12110,NULL),(27,28050,14,8260,NULL,12108,NULL),(27,28051,14,8260,NULL,12109,NULL),(27,28052,14,8261,NULL,12110,NULL),(27,28053,14,8261,NULL,12108,NULL),(27,28054,14,8261,NULL,12109,NULL),(27,28055,13,4130,NULL,6054,NULL),(27,28056,13,4130,NULL,6055,NULL),(28,28057,14,8260,NULL,12110,NULL),(28,28058,14,8260,NULL,12108,NULL),(28,28059,14,8260,NULL,12109,NULL),(28,28060,14,8261,NULL,12110,NULL),(28,28061,14,8261,NULL,12108,NULL),(28,28062,14,8261,NULL,12109,NULL),(28,28063,13,4130,NULL,6054,NULL),(28,28064,13,4130,NULL,6055,NULL),(29,28065,14,8260,NULL,12110,NULL),(29,28066,14,8260,NULL,12108,NULL),(29,28067,14,8260,NULL,12109,NULL),(29,28068,14,8261,NULL,12110,NULL),(29,28069,14,8261,NULL,12108,NULL),(29,28070,14,8261,NULL,12109,NULL),(29,28071,13,4130,NULL,6054,NULL),(29,28072,13,4130,NULL,6055,NULL),(30,28073,14,8260,NULL,12110,NULL),(30,28074,14,8260,NULL,12108,NULL),(30,28075,14,8260,NULL,12109,NULL),(30,28076,14,8261,NULL,12110,NULL),(30,28077,14,8261,NULL,12108,NULL),(30,28078,14,8261,NULL,12109,NULL),(30,28079,13,4130,NULL,6054,NULL),(30,28080,13,4130,NULL,6055,NULL),(34,28081,14,8260,NULL,12110,NULL),(34,28082,14,8260,NULL,12108,NULL),(34,28083,14,8260,NULL,12109,NULL),(34,28084,14,8261,NULL,12110,NULL),(34,28085,14,8261,NULL,12108,NULL),(34,28086,14,8261,NULL,12109,NULL),(34,28087,13,4130,NULL,6054,NULL),(34,28088,13,4130,NULL,6055,NULL),(30,28089,14,8260,NULL,12110,NULL),(30,28090,14,8260,NULL,12108,NULL),(30,28091,14,8260,NULL,12109,NULL),(30,28092,14,8261,NULL,12110,NULL),(30,28093,14,8261,NULL,12108,NULL),(30,28094,14,8261,NULL,12109,NULL),(30,28095,13,4130,NULL,6054,NULL),(30,28096,13,4130,NULL,6055,NULL),(37,28097,14,8260,NULL,12110,NULL),(37,28098,14,8260,NULL,12108,NULL),(37,28099,14,8260,NULL,12109,NULL),(37,28100,14,8261,NULL,12110,NULL),(37,28101,14,8261,NULL,12108,NULL),(37,28102,14,8261,NULL,12109,NULL),(37,28103,13,4130,NULL,6054,NULL),(37,28104,13,4130,NULL,6055,NULL),(35,28105,14,8260,NULL,12110,NULL),(35,28106,14,8260,NULL,12108,NULL),(35,28107,14,8260,NULL,12109,NULL),(35,28108,14,8261,NULL,12110,NULL),(35,28109,14,8261,NULL,12108,NULL),(35,28110,14,8261,NULL,12109,NULL),(35,28111,13,4130,NULL,6054,NULL),(35,28112,13,4130,NULL,6055,NULL),(40,28113,14,8260,NULL,12110,NULL),(40,28114,14,8260,NULL,12108,NULL),(40,28115,14,8260,NULL,12109,NULL),(40,28116,14,8261,NULL,12110,NULL),(40,28117,14,8261,NULL,12108,NULL),(40,28118,14,8261,NULL,12109,NULL),(40,28119,13,4130,NULL,6054,NULL),(40,28120,13,4130,NULL,6055,NULL),(39,28121,14,8260,NULL,12110,NULL),(39,28122,14,8260,NULL,12108,NULL),(39,28123,14,8260,NULL,12109,NULL),(39,28124,14,8261,NULL,12110,NULL),(39,28125,14,8261,NULL,12108,NULL),(39,28126,14,8261,NULL,12109,NULL),(39,28127,13,4130,NULL,6054,NULL),(39,28128,13,4130,NULL,6055,NULL),(42,28129,14,8260,NULL,12110,NULL),(42,28130,14,8260,NULL,12108,NULL),(42,28131,14,8260,NULL,12109,NULL),(42,28132,14,8261,NULL,12110,NULL),(42,28133,14,8261,NULL,12108,NULL),(42,28134,14,8261,NULL,12109,NULL),(42,28135,13,4130,NULL,6054,NULL),(42,28136,13,4130,NULL,6055,NULL),(43,28137,14,4123,NULL,10367,NULL),(43,28138,14,4123,NULL,10368,NULL),(43,28139,14,4122,NULL,10367,NULL),(43,28140,14,4122,NULL,10368,NULL),(43,28141,13,2061,NULL,5183,NULL),(43,28142,13,2061,NULL,5184,NULL),(43,28143,15,8244,NULL,20735,NULL),(43,28144,15,8244,NULL,20736,NULL),(43,28145,15,8244,NULL,20737,NULL),(43,28146,15,8245,NULL,20735,NULL),(43,28147,15,8245,NULL,20736,NULL),(43,28148,15,8245,NULL,20737,NULL),(43,28149,15,8246,NULL,20735,NULL),(43,28150,15,8246,NULL,20736,NULL),(43,28151,15,8246,NULL,20737,NULL),(43,28152,15,8247,NULL,20735,NULL),(43,28153,15,8247,NULL,20736,NULL),(43,28154,15,8247,NULL,20737,NULL),(44,28155,14,8260,NULL,12110,NULL),(44,28156,14,8260,NULL,12108,NULL),(44,28157,14,8260,NULL,12109,NULL),(44,28158,14,8261,NULL,12110,NULL),(44,28159,14,8261,NULL,12108,NULL),(44,28160,14,8261,NULL,12109,NULL),(44,28161,13,4130,NULL,6054,NULL),(44,28162,13,4130,NULL,6055,NULL),(46,28163,14,8260,NULL,12110,NULL),(46,28164,14,8260,NULL,12108,NULL),(46,28165,14,8260,NULL,12109,NULL),(46,28166,14,8261,NULL,12110,NULL),(46,28167,14,8261,NULL,12108,NULL),(46,28168,14,8261,NULL,12109,NULL),(46,28169,13,4130,NULL,6054,NULL),(46,28170,13,4130,NULL,6055,NULL),(47,28171,14,4123,NULL,10367,NULL),(47,28172,14,4123,NULL,10368,NULL),(47,28173,14,4122,NULL,10367,NULL),(47,28174,14,4122,NULL,10368,NULL),(47,28175,13,2061,NULL,5183,NULL),(47,28176,13,2061,NULL,5184,NULL),(47,28177,15,8244,NULL,20735,NULL),(47,28178,15,8244,NULL,20736,NULL),(47,28179,15,8244,NULL,20737,NULL),(47,28180,15,8245,NULL,20735,NULL),(47,28181,15,8245,NULL,20736,NULL),(47,28182,15,8245,NULL,20737,NULL),(47,28183,15,8246,NULL,20735,NULL),(47,28184,15,8246,NULL,20736,NULL),(47,28185,15,8246,NULL,20737,NULL),(47,28186,15,8247,NULL,20735,NULL),(47,28187,15,8247,NULL,20736,NULL),(47,28188,15,8247,NULL,20737,NULL),(48,28189,14,8260,NULL,12110,NULL),(48,28190,14,8260,NULL,12108,NULL),(48,28191,14,8260,NULL,12109,NULL),(48,28192,14,8261,NULL,12110,NULL),(48,28193,14,8261,NULL,12108,NULL),(48,28194,14,8261,NULL,12109,NULL),(48,28195,13,4130,NULL,6054,NULL),(48,28196,13,4130,NULL,6055,NULL),(53,28436,17,32986,NULL,82945,NULL),(53,28435,17,32986,NULL,82947,NULL),(53,28434,17,32986,NULL,82946,NULL),(53,28433,17,32983,NULL,82945,NULL),(53,28432,17,32983,NULL,82947,NULL),(53,28431,17,32983,NULL,82946,NULL),(53,28430,17,32984,NULL,82945,NULL),(53,28429,17,32984,NULL,82947,NULL),(53,28428,17,32984,NULL,82946,NULL),(53,28427,17,32981,NULL,82945,NULL),(53,28426,17,32981,NULL,82947,NULL),(53,28425,17,32981,NULL,82946,NULL),(53,28424,17,32982,NULL,82945,NULL),(53,28423,17,32982,NULL,82947,NULL),(53,28422,17,32982,NULL,82946,NULL),(53,28421,17,32985,NULL,82945,NULL),(53,28420,17,32985,NULL,82947,NULL),(53,28419,17,32985,NULL,82946,NULL),(53,28418,15,8246,NULL,20736,NULL),(53,28417,15,8245,NULL,20736,NULL),(53,28416,16,16490,NULL,41473,NULL),(53,28415,16,16490,NULL,41472,NULL),(53,28414,16,16492,NULL,41473,NULL),(53,28413,16,16492,NULL,41472,NULL),(53,28412,16,16493,NULL,41473,NULL),(53,28411,16,16493,NULL,41472,NULL),(53,28410,16,16491,NULL,41473,NULL),(53,28409,16,16491,NULL,41472,NULL),(53,28408,14,4122,NULL,10368,NULL),(53,28407,14,4123,NULL,10368,NULL),(53,28406,17,32986,NULL,82945,NULL),(53,28405,17,32986,NULL,82947,NULL),(53,28404,17,32986,NULL,82946,NULL),(53,28403,17,32983,NULL,82945,NULL),(53,28402,17,32983,NULL,82947,NULL),(53,28401,17,32983,NULL,82946,NULL),(53,28400,17,32984,NULL,82945,NULL),(53,28399,17,32984,NULL,82947,NULL),(53,28398,17,32984,NULL,82946,NULL),(53,28397,17,32981,NULL,82945,NULL),(53,28396,17,32981,NULL,82947,NULL),(53,28395,17,32981,NULL,82946,NULL),(53,28394,17,32982,NULL,82945,NULL),(53,28393,17,32982,NULL,82947,NULL),(53,28392,17,32982,NULL,82946,NULL),(53,28391,17,32985,NULL,82945,NULL),(53,28390,17,32985,NULL,82947,NULL),(53,28389,17,32985,NULL,82946,NULL),(53,28388,15,8246,NULL,20736,NULL),(53,28387,15,8245,NULL,20736,NULL),(53,28386,16,16490,NULL,41473,NULL),(53,28385,16,16490,NULL,41472,NULL),(53,28384,16,16492,NULL,41473,NULL),(53,28383,16,16492,NULL,41472,NULL),(53,28382,16,16493,NULL,41473,NULL),(53,28381,16,16493,NULL,41472,NULL),(53,28380,16,16491,NULL,41473,NULL),(53,28379,16,16491,NULL,41472,NULL),(53,28378,14,4122,NULL,10368,NULL),(53,28377,14,4123,NULL,10368,NULL),(51,28376,17,32986,NULL,82945,NULL),(51,28375,17,32986,NULL,82947,NULL),(51,28374,17,32986,NULL,82946,NULL),(51,28373,17,32983,NULL,82945,NULL),(51,28372,17,32983,NULL,82947,NULL),(51,28371,17,32983,NULL,82946,NULL),(51,28370,17,32984,NULL,82945,NULL),(51,28369,17,32984,NULL,82947,NULL),(51,28368,17,32984,NULL,82946,NULL),(51,28367,17,32981,NULL,82945,NULL),(51,28366,17,32981,NULL,82947,NULL),(51,28365,17,32981,NULL,82946,NULL),(51,28364,17,32982,NULL,82945,NULL),(51,28363,17,32982,NULL,82947,NULL),(51,28362,17,32982,NULL,82946,NULL),(51,28361,17,32985,NULL,82945,NULL),(51,28360,17,32985,NULL,82947,NULL),(51,28359,17,32985,NULL,82946,NULL),(51,28358,15,8246,NULL,20736,NULL),(51,28357,15,8245,NULL,20736,NULL),(51,28356,16,16490,NULL,41473,NULL),(51,28355,16,16490,NULL,41472,NULL),(51,28354,16,16492,NULL,41473,NULL),(51,28353,16,16492,NULL,41472,NULL),(51,28352,16,16493,NULL,41473,NULL),(51,28351,16,16493,NULL,41472,NULL),(51,28350,16,16491,NULL,41473,NULL),(51,28349,16,16491,NULL,41472,NULL),(51,28348,14,4122,NULL,10368,NULL),(51,28347,14,4123,NULL,10368,NULL),(49,30428,17,32986,NULL,82945,NULL),(49,30427,17,32986,NULL,82947,NULL),(49,30426,17,32986,NULL,82946,NULL),(49,30425,17,32986,NULL,82944,NULL),(49,30424,17,32983,NULL,82945,NULL),(49,30423,17,32983,NULL,82947,NULL),(49,30422,17,32983,NULL,82946,NULL),(49,30421,17,32983,NULL,82944,NULL),(49,30420,17,32984,NULL,82945,NULL),(49,30419,17,32984,NULL,82947,NULL),(49,30418,17,32984,NULL,82946,NULL),(49,30417,17,32984,NULL,82944,NULL),(49,30416,17,32981,NULL,82945,NULL),(49,30415,17,32981,NULL,82947,NULL),(49,30414,17,32981,NULL,82946,NULL),(49,30413,17,32981,NULL,82944,NULL),(49,30412,17,32982,NULL,82945,NULL),(49,30411,17,32982,NULL,82947,NULL),(49,30410,17,32982,NULL,82946,NULL),(49,30409,17,32982,NULL,82944,NULL),(49,30408,17,32985,NULL,82945,NULL),(49,30407,17,32985,NULL,82947,NULL),(49,30406,17,32985,NULL,82946,NULL),(49,30405,17,32985,NULL,82944,NULL),(49,30404,15,8246,NULL,20736,NULL),(49,30403,15,8245,NULL,20736,NULL),(49,30402,16,16490,NULL,41473,NULL),(49,30401,16,16490,NULL,41472,NULL),(49,30400,16,16492,NULL,41473,NULL),(49,30399,16,16492,NULL,41472,NULL),(52,29738,17,32986,NULL,82945,NULL),(52,29737,17,32986,NULL,82947,NULL),(52,29736,17,32986,NULL,82946,NULL),(52,29735,17,32983,NULL,82945,NULL),(52,29734,17,32983,NULL,82947,NULL),(52,29733,17,32983,NULL,82946,NULL),(52,29732,17,32984,NULL,82945,NULL),(52,29731,17,32984,NULL,82947,NULL),(52,29730,17,32984,NULL,82946,NULL),(52,29729,17,32981,NULL,82945,NULL),(52,29728,17,32981,NULL,82947,NULL),(52,29727,17,32981,NULL,82946,NULL),(52,29726,17,32982,NULL,82945,NULL),(52,29725,17,32982,NULL,82947,NULL),(52,29724,17,32982,NULL,82946,NULL),(52,29723,17,32985,NULL,82945,NULL),(52,29722,17,32985,NULL,82947,NULL),(52,29721,17,32985,NULL,82946,NULL),(52,29720,15,8246,NULL,20736,NULL),(52,29719,15,8245,NULL,20736,NULL),(52,29718,16,16490,NULL,41473,NULL),(52,29717,16,16490,NULL,41472,NULL),(52,29716,16,16492,NULL,41473,NULL),(52,29715,16,16492,NULL,41472,NULL),(52,29714,16,16493,NULL,41473,NULL),(52,29713,16,16493,NULL,41472,NULL),(52,29712,16,16491,NULL,41473,NULL),(52,29711,16,16491,NULL,41472,NULL),(52,29710,14,4122,NULL,10368,NULL),(52,29709,14,4123,NULL,10368,NULL),(60,29096,17,32986,NULL,82945,NULL),(60,29095,17,32986,NULL,82947,NULL),(60,29094,17,32986,NULL,82946,NULL),(60,29093,17,32983,NULL,82945,NULL),(60,29092,17,32983,NULL,82947,NULL),(60,29091,17,32983,NULL,82946,NULL),(60,29090,17,32984,NULL,82945,NULL),(60,29089,17,32984,NULL,82947,NULL),(60,29088,17,32984,NULL,82946,NULL),(60,29087,17,32981,NULL,82945,NULL),(60,29086,17,32981,NULL,82947,NULL),(60,29085,17,32981,NULL,82946,NULL),(60,29084,17,32982,NULL,82945,NULL),(60,29083,17,32982,NULL,82947,NULL),(60,29082,17,32982,NULL,82946,NULL),(60,29081,17,32985,NULL,82945,NULL),(60,29080,17,32985,NULL,82947,NULL),(60,29079,17,32985,NULL,82946,NULL),(60,29078,15,8246,NULL,20736,NULL),(60,29077,15,8245,NULL,20736,NULL),(60,29076,16,16490,NULL,41473,NULL),(60,29075,16,16490,NULL,41472,NULL),(60,29074,16,16492,NULL,41473,NULL),(60,29073,16,16492,NULL,41472,NULL),(60,29072,16,16493,NULL,41473,NULL),(60,29071,16,16493,NULL,41472,NULL),(60,29070,16,16491,NULL,41473,NULL),(60,29069,16,16491,NULL,41472,NULL),(60,29068,14,4122,NULL,10368,NULL),(60,29067,14,4123,NULL,10368,NULL),(59,29066,17,32986,NULL,82945,NULL),(59,29065,17,32986,NULL,82947,NULL),(59,29064,17,32986,NULL,82946,NULL),(59,29063,17,32983,NULL,82945,NULL),(59,29062,17,32983,NULL,82947,NULL),(59,29061,17,32983,NULL,82946,NULL),(59,29060,17,32984,NULL,82945,NULL),(59,29059,17,32984,NULL,82947,NULL),(59,29058,17,32984,NULL,82946,NULL),(59,29057,17,32981,NULL,82945,NULL),(59,29056,17,32981,NULL,82947,NULL),(59,29055,17,32981,NULL,82946,NULL),(59,29054,17,32982,NULL,82945,NULL),(59,29053,17,32982,NULL,82947,NULL),(59,29052,17,32982,NULL,82946,NULL),(59,29051,17,32985,NULL,82945,NULL),(59,29050,17,32985,NULL,82947,NULL),(59,29049,17,32985,NULL,82946,NULL),(59,29048,15,8246,NULL,20736,NULL),(59,29047,15,8245,NULL,20736,NULL),(59,29046,16,16490,NULL,41473,NULL),(59,29045,16,16490,NULL,41472,NULL),(59,29044,16,16492,NULL,41473,NULL),(59,29043,16,16492,NULL,41472,NULL),(59,29042,16,16493,NULL,41473,NULL),(59,29041,16,16493,NULL,41472,NULL),(59,29040,16,16491,NULL,41473,NULL),(59,29039,16,16491,NULL,41472,NULL),(59,29038,14,4122,NULL,10368,NULL),(59,29037,14,4123,NULL,10368,NULL),(58,29006,17,32986,NULL,82945,NULL),(58,29005,17,32986,NULL,82947,NULL),(58,29004,17,32986,NULL,82946,NULL),(58,29003,17,32983,NULL,82945,NULL),(58,29002,17,32983,NULL,82947,NULL),(58,29001,17,32983,NULL,82946,NULL),(58,29000,17,32984,NULL,82945,NULL),(58,28999,17,32984,NULL,82947,NULL),(58,28998,17,32984,NULL,82946,NULL),(58,28997,17,32981,NULL,82945,NULL),(58,28996,17,32981,NULL,82947,NULL),(58,28995,17,32981,NULL,82946,NULL),(58,28994,17,32982,NULL,82945,NULL),(58,28993,17,32982,NULL,82947,NULL),(58,28992,17,32982,NULL,82946,NULL),(58,28991,17,32985,NULL,82945,NULL),(58,28990,17,32985,NULL,82947,NULL),(58,28989,17,32985,NULL,82946,NULL),(58,28988,15,8246,NULL,20736,NULL),(58,28987,15,8245,NULL,20736,NULL),(58,28986,16,16490,NULL,41473,NULL),(58,28985,16,16490,NULL,41472,NULL),(58,28984,16,16492,NULL,41473,NULL),(58,28983,16,16492,NULL,41472,NULL),(58,28982,16,16493,NULL,41473,NULL),(58,28981,16,16493,NULL,41472,NULL),(58,28980,16,16491,NULL,41473,NULL),(58,28979,16,16491,NULL,41472,NULL),(58,28978,14,4122,NULL,10368,NULL),(58,28977,14,4123,NULL,10368,NULL),(57,28976,17,32986,NULL,82945,NULL),(57,28975,17,32986,NULL,82947,NULL),(57,28974,17,32986,NULL,82946,NULL),(57,28973,17,32983,NULL,82945,NULL),(57,28972,17,32983,NULL,82947,NULL),(57,28971,17,32983,NULL,82946,NULL),(57,28970,17,32984,NULL,82945,NULL),(57,28969,17,32984,NULL,82947,NULL),(57,28968,17,32984,NULL,82946,NULL),(57,28967,17,32981,NULL,82945,NULL),(57,28966,17,32981,NULL,82947,NULL),(57,28965,17,32981,NULL,82946,NULL),(57,28964,17,32982,NULL,82945,NULL),(57,28963,17,32982,NULL,82947,NULL),(57,28962,17,32982,NULL,82946,NULL),(57,28961,17,32985,NULL,82945,NULL),(57,28960,17,32985,NULL,82947,NULL),(57,28959,17,32985,NULL,82946,NULL),(57,28958,15,8246,NULL,20736,NULL),(57,28957,15,8245,NULL,20736,NULL),(57,28956,16,16490,NULL,41473,NULL),(57,28955,16,16490,NULL,41472,NULL),(57,28954,16,16492,NULL,41473,NULL),(57,28953,16,16492,NULL,41472,NULL),(57,28952,16,16493,NULL,41473,NULL),(57,28951,16,16493,NULL,41472,NULL),(57,28950,16,16491,NULL,41473,NULL),(57,28949,16,16491,NULL,41472,NULL),(57,28948,14,4122,NULL,10368,NULL),(57,28947,14,4123,NULL,10368,NULL),(56,28946,17,32986,NULL,82945,NULL),(56,28945,17,32986,NULL,82947,NULL),(56,28944,17,32986,NULL,82946,NULL),(56,28943,17,32983,NULL,82945,NULL),(56,28942,17,32983,NULL,82947,NULL),(56,28941,17,32983,NULL,82946,NULL),(56,28940,17,32984,NULL,82945,NULL),(56,28939,17,32984,NULL,82947,NULL),(56,28938,17,32984,NULL,82946,NULL),(56,28937,17,32981,NULL,82945,NULL),(56,28936,17,32981,NULL,82947,NULL),(56,28935,17,32981,NULL,82946,NULL),(56,28934,17,32982,NULL,82945,NULL),(56,28933,17,32982,NULL,82947,NULL),(56,28932,17,32982,NULL,82946,NULL),(56,28931,17,32985,NULL,82945,NULL),(56,28930,17,32985,NULL,82947,NULL),(56,28929,17,32985,NULL,82946,NULL),(56,28928,15,8246,NULL,20736,NULL),(56,28927,15,8245,NULL,20736,NULL),(56,28926,16,16490,NULL,41473,NULL),(56,28925,16,16490,NULL,41472,NULL),(56,28924,16,16492,NULL,41473,NULL),(56,28923,16,16492,NULL,41472,NULL),(56,28922,16,16493,NULL,41473,NULL),(56,28921,16,16493,NULL,41472,NULL),(56,28920,16,16491,NULL,41473,NULL),(56,28919,16,16491,NULL,41472,NULL),(56,28918,14,4122,NULL,10368,NULL),(56,28917,14,4123,NULL,10368,NULL),(55,28916,17,32986,NULL,82945,NULL),(55,28915,17,32986,NULL,82947,NULL),(55,28914,17,32986,NULL,82946,NULL),(55,28913,17,32983,NULL,82945,NULL),(55,28912,17,32983,NULL,82947,NULL),(55,28911,17,32983,NULL,82946,NULL),(55,28910,17,32984,NULL,82945,NULL),(55,28909,17,32984,NULL,82947,NULL),(55,28908,17,32984,NULL,82946,NULL),(55,28907,17,32981,NULL,82945,NULL),(55,28906,17,32981,NULL,82947,NULL),(55,28905,17,32981,NULL,82946,NULL),(55,28904,17,32982,NULL,82945,NULL),(55,28903,17,32982,NULL,82947,NULL),(55,28902,17,32982,NULL,82946,NULL),(55,28901,17,32985,NULL,82945,NULL),(55,28900,17,32985,NULL,82947,NULL),(55,28899,17,32985,NULL,82946,NULL),(55,28898,15,8246,NULL,20736,NULL),(55,28897,15,8245,NULL,20736,NULL),(55,28896,16,16490,NULL,41473,NULL),(55,28895,16,16490,NULL,41472,NULL),(55,28894,16,16492,NULL,41473,NULL),(55,28893,16,16492,NULL,41472,NULL),(55,28892,16,16493,NULL,41473,NULL),(55,28891,16,16493,NULL,41472,NULL),(55,28890,16,16491,NULL,41473,NULL),(55,28889,16,16491,NULL,41472,NULL),(55,28888,14,4122,NULL,10368,NULL),(55,28887,14,4123,NULL,10368,NULL),(61,29216,17,32986,NULL,82945,NULL),(61,29215,17,32986,NULL,82947,NULL),(61,29214,17,32986,NULL,82946,NULL),(61,29213,17,32983,NULL,82945,NULL),(61,29212,17,32983,NULL,82947,NULL),(61,29211,17,32983,NULL,82946,NULL),(61,29210,17,32984,NULL,82945,NULL),(61,29209,17,32984,NULL,82947,NULL),(61,29208,17,32984,NULL,82946,NULL),(61,29207,17,32981,NULL,82945,NULL),(61,29206,17,32981,NULL,82947,NULL),(61,29205,17,32981,NULL,82946,NULL),(61,29204,17,32982,NULL,82945,NULL),(61,29203,17,32982,NULL,82947,NULL),(61,29202,17,32982,NULL,82946,NULL),(61,29201,17,32985,NULL,82945,NULL),(61,29200,17,32985,NULL,82947,NULL),(61,29199,17,32985,NULL,82946,NULL),(61,29198,15,8246,NULL,20736,NULL),(61,29197,15,8245,NULL,20736,NULL),(61,29196,16,16490,NULL,41473,NULL),(61,29195,16,16490,NULL,41472,NULL),(61,29194,16,16492,NULL,41473,NULL),(61,29193,16,16492,NULL,41472,NULL),(61,29192,16,16493,NULL,41473,NULL),(61,29191,16,16493,NULL,41472,NULL),(61,29190,16,16491,NULL,41473,NULL),(61,29189,16,16491,NULL,41472,NULL),(61,29188,14,4122,NULL,10368,NULL),(61,29187,14,4123,NULL,10368,NULL),(62,29306,17,32986,NULL,82945,NULL),(62,29305,17,32986,NULL,82947,NULL),(62,29304,17,32986,NULL,82946,NULL),(62,29303,17,32983,NULL,82945,NULL),(62,29302,17,32983,NULL,82947,NULL),(62,29301,17,32983,NULL,82946,NULL),(62,29300,17,32984,NULL,82945,NULL),(62,29299,17,32984,NULL,82947,NULL),(62,29298,17,32984,NULL,82946,NULL),(62,29297,17,32981,NULL,82945,NULL),(62,29296,17,32981,NULL,82947,NULL),(62,29295,17,32981,NULL,82946,NULL),(62,29294,17,32982,NULL,82945,NULL),(62,29293,17,32982,NULL,82947,NULL),(62,29292,17,32982,NULL,82946,NULL),(62,29291,17,32985,NULL,82945,NULL),(62,29290,17,32985,NULL,82947,NULL),(62,29289,17,32985,NULL,82946,NULL),(62,29288,15,8246,NULL,20736,NULL),(62,29287,15,8245,NULL,20736,NULL),(62,29286,16,16490,NULL,41473,NULL),(62,29285,16,16490,NULL,41472,NULL),(62,29284,16,16492,NULL,41473,NULL),(62,29283,16,16492,NULL,41472,NULL),(62,29282,16,16493,NULL,41473,NULL),(62,29281,16,16493,NULL,41472,NULL),(62,29280,16,16491,NULL,41473,NULL),(62,29279,16,16491,NULL,41472,NULL),(62,29278,14,4122,NULL,10368,NULL),(62,29277,14,4123,NULL,10368,NULL),(63,29396,17,32986,NULL,82945,NULL),(63,29395,17,32986,NULL,82947,NULL),(63,29394,17,32986,NULL,82946,NULL),(63,29393,17,32983,NULL,82945,NULL),(63,29392,17,32983,NULL,82947,NULL),(63,29391,17,32983,NULL,82946,NULL),(63,29390,17,32984,NULL,82945,NULL),(63,29389,17,32984,NULL,82947,NULL),(63,29388,17,32984,NULL,82946,NULL),(63,29387,17,32981,NULL,82945,NULL),(63,29386,17,32981,NULL,82947,NULL),(63,29385,17,32981,NULL,82946,NULL),(63,29384,17,32982,NULL,82945,NULL),(63,29383,17,32982,NULL,82947,NULL),(63,29382,17,32982,NULL,82946,NULL),(63,29381,17,32985,NULL,82945,NULL),(63,29380,17,32985,NULL,82947,NULL),(63,29379,17,32985,NULL,82946,NULL),(63,29378,15,8246,NULL,20736,NULL),(63,29377,15,8245,NULL,20736,NULL),(63,29376,16,16490,NULL,41473,NULL),(63,29375,16,16490,NULL,41472,NULL),(63,29374,16,16492,NULL,41473,NULL),(63,29373,16,16492,NULL,41472,NULL),(63,29372,16,16493,NULL,41473,NULL),(63,29371,16,16493,NULL,41472,NULL),(63,29370,16,16491,NULL,41473,NULL),(63,29369,16,16491,NULL,41472,NULL),(63,29368,14,4122,NULL,10368,NULL),(63,29367,14,4123,NULL,10368,NULL),(64,29606,17,32986,NULL,82945,NULL),(64,29605,17,32986,NULL,82947,NULL),(64,29604,17,32986,NULL,82946,NULL),(64,29603,17,32983,NULL,82945,NULL),(64,29602,17,32983,NULL,82947,NULL),(64,29601,17,32983,NULL,82946,NULL),(64,29600,17,32984,NULL,82945,NULL),(64,29599,17,32984,NULL,82947,NULL),(64,29598,17,32984,NULL,82946,NULL),(64,29597,17,32981,NULL,82945,NULL),(64,29596,17,32981,NULL,82947,NULL),(64,29595,17,32981,NULL,82946,NULL),(64,29594,17,32982,NULL,82945,NULL),(64,29593,17,32982,NULL,82947,NULL),(64,29592,17,32982,NULL,82946,NULL),(64,29591,17,32985,NULL,82945,NULL),(64,29590,17,32985,NULL,82947,NULL),(64,29589,17,32985,NULL,82946,NULL),(64,29588,15,8246,NULL,20736,NULL),(64,29587,15,8245,NULL,20736,NULL),(64,29586,16,16490,NULL,41473,NULL),(64,29585,16,16490,NULL,41472,NULL),(64,29584,16,16492,NULL,41473,NULL),(64,29583,16,16492,NULL,41472,NULL),(64,29582,16,16493,NULL,41473,NULL),(64,29581,16,16493,NULL,41472,NULL),(64,29580,16,16491,NULL,41473,NULL),(64,29579,16,16491,NULL,41472,NULL),(64,29578,14,4122,NULL,10368,NULL),(64,29577,14,4123,NULL,10368,NULL),(50,30362,17,32986,NULL,82945,NULL),(50,30361,17,32986,NULL,82947,NULL),(50,30360,17,32986,NULL,82946,NULL),(50,30359,17,32983,NULL,82945,NULL),(50,30358,17,32983,NULL,82947,NULL),(50,30357,17,32983,NULL,82946,NULL),(50,30356,17,32984,NULL,82945,NULL),(50,30355,17,32984,NULL,82947,NULL),(50,30354,17,32984,NULL,82946,NULL),(50,30353,17,32981,NULL,82945,NULL),(50,30352,17,32981,NULL,82947,NULL),(50,30351,17,32981,NULL,82946,NULL),(50,30350,17,32982,NULL,82945,NULL),(50,30349,17,32982,NULL,82947,NULL),(50,30348,17,32982,NULL,82946,NULL),(50,30347,17,32985,NULL,82945,NULL),(50,30346,17,32985,NULL,82947,NULL),(50,30345,17,32985,NULL,82946,NULL),(50,30344,15,8246,NULL,20736,NULL),(50,30343,15,8245,NULL,20736,NULL),(50,30342,16,16490,NULL,41473,NULL),(50,30341,16,16490,NULL,41472,NULL),(50,30340,16,16492,NULL,41473,NULL),(50,30339,16,16492,NULL,41472,NULL),(49,30398,16,16493,NULL,41473,NULL),(49,30397,16,16493,NULL,41472,NULL),(49,30396,16,16491,NULL,41473,NULL),(49,30395,16,16491,NULL,41472,NULL),(49,30394,14,4122,NULL,10368,NULL),(49,30393,14,4123,NULL,10368,NULL),(66,29487,14,4123,NULL,10368,NULL),(66,29488,14,4122,NULL,10368,NULL),(66,29489,16,16491,NULL,41472,NULL),(66,29490,16,16491,NULL,41473,NULL),(66,29491,16,16493,NULL,41472,NULL),(66,29492,16,16493,NULL,41473,NULL),(66,29493,16,16492,NULL,41472,NULL),(66,29494,16,16492,NULL,41473,NULL),(66,29495,16,16490,NULL,41472,NULL),(66,29496,16,16490,NULL,41473,NULL),(66,29497,15,8245,NULL,20736,NULL),(66,29498,15,8246,NULL,20736,NULL),(66,29499,17,32985,NULL,82946,NULL),(66,29500,17,32985,NULL,82947,NULL),(66,29501,17,32985,NULL,82945,NULL),(66,29502,17,32982,NULL,82946,NULL),(66,29503,17,32982,NULL,82947,NULL),(66,29504,17,32982,NULL,82945,NULL),(66,29505,17,32981,NULL,82946,NULL),(66,29506,17,32981,NULL,82947,NULL),(66,29507,17,32981,NULL,82945,NULL),(66,29508,17,32984,NULL,82946,NULL),(66,29509,17,32984,NULL,82947,NULL),(66,29510,17,32984,NULL,82945,NULL),(66,29511,17,32983,NULL,82946,NULL),(66,29512,17,32983,NULL,82947,NULL),(66,29513,17,32983,NULL,82945,NULL),(66,29514,17,32986,NULL,82946,NULL),(66,29515,17,32986,NULL,82947,NULL),(66,29516,17,32986,NULL,82945,NULL),(50,30338,16,16493,NULL,41473,NULL),(50,30337,16,16493,NULL,41472,NULL),(50,30336,16,16491,NULL,41473,NULL),(50,30335,16,16491,NULL,41472,NULL),(50,30334,14,4122,NULL,10368,NULL),(50,30333,14,4123,NULL,10368,NULL),(76,30458,17,32986,NULL,82945,NULL),(76,30457,17,32986,NULL,82947,NULL),(76,30456,17,32986,NULL,82946,NULL),(76,30455,17,32983,NULL,82945,NULL),(76,30454,17,32983,NULL,82947,NULL),(76,30453,17,32983,NULL,82946,NULL),(76,30452,17,32984,NULL,82945,NULL),(76,30451,17,32984,NULL,82947,NULL),(76,30450,17,32984,NULL,82946,NULL),(76,30449,17,32981,NULL,82945,NULL),(76,30448,17,32981,NULL,82947,NULL),(76,30447,17,32981,NULL,82946,NULL),(76,30446,17,32982,NULL,82945,NULL),(76,30445,17,32982,NULL,82947,NULL),(76,30444,17,32982,NULL,82946,NULL),(76,30443,17,32985,NULL,82945,NULL),(76,30442,17,32985,NULL,82947,NULL),(76,30441,17,32985,NULL,82946,NULL),(76,30440,15,8246,NULL,20736,NULL),(76,30439,15,8245,NULL,20736,NULL),(76,30438,16,16490,NULL,41473,NULL),(76,30437,16,16490,NULL,41472,NULL),(76,30436,16,16492,NULL,41473,NULL),(76,30435,16,16492,NULL,41472,NULL),(76,30434,16,16493,NULL,41473,NULL),(76,30433,16,16493,NULL,41472,NULL),(76,30432,16,16491,NULL,41473,NULL),(76,30431,16,16491,NULL,41472,NULL),(76,30430,14,4122,NULL,10368,NULL),(76,30429,14,4123,NULL,10368,NULL),(77,30488,17,32986,NULL,82945,NULL),(77,30487,17,32986,NULL,82947,NULL),(77,30486,17,32986,NULL,82946,NULL),(77,30485,17,32983,NULL,82945,NULL),(77,30484,17,32983,NULL,82947,NULL),(77,30483,17,32983,NULL,82946,NULL),(77,30482,17,32984,NULL,82945,NULL),(77,30481,17,32984,NULL,82947,NULL),(77,30480,17,32984,NULL,82946,NULL),(77,30479,17,32981,NULL,82945,NULL),(77,30478,17,32981,NULL,82947,NULL),(77,30477,17,32981,NULL,82946,NULL),(77,30476,17,32982,NULL,82945,NULL),(77,30475,17,32982,NULL,82947,NULL),(77,30474,17,32982,NULL,82946,NULL),(77,30473,17,32985,NULL,82945,NULL),(77,30472,17,32985,NULL,82947,NULL),(77,30471,17,32985,NULL,82946,NULL),(77,30470,15,8246,NULL,20736,NULL),(77,30469,15,8245,NULL,20736,NULL),(77,30468,16,16490,NULL,41473,NULL),(77,30467,16,16490,NULL,41472,NULL),(77,30466,16,16492,NULL,41473,NULL),(77,30465,16,16492,NULL,41472,NULL),(77,30464,16,16493,NULL,41473,NULL),(77,30463,16,16493,NULL,41472,NULL),(77,30462,16,16491,NULL,41473,NULL),(77,30461,16,16491,NULL,41472,NULL),(77,30460,14,4122,NULL,10368,NULL),(77,30459,14,4123,NULL,10368,NULL),(78,30489,14,4123,NULL,10368,NULL),(78,30490,14,4122,NULL,10368,NULL),(78,30491,16,16491,NULL,41472,NULL),(78,30492,16,16491,NULL,41473,NULL),(78,30493,16,16493,NULL,41472,NULL),(78,30494,16,16493,NULL,41473,NULL),(78,30495,16,16492,NULL,41472,NULL),(78,30496,16,16492,NULL,41473,NULL),(78,30497,16,16490,NULL,41472,NULL),(78,30498,16,16490,NULL,41473,NULL),(78,30499,15,8245,NULL,20736,NULL),(78,30500,15,8246,NULL,20736,NULL),(78,30501,17,32985,NULL,82946,NULL),(78,30502,17,32985,NULL,82947,NULL),(78,30503,17,32985,NULL,82945,NULL),(78,30504,17,32982,NULL,82946,NULL),(78,30505,17,32982,NULL,82947,NULL),(78,30506,17,32982,NULL,82945,NULL),(78,30507,17,32981,NULL,82946,NULL),(78,30508,17,32981,NULL,82947,NULL),(78,30509,17,32981,NULL,82945,NULL),(78,30510,17,32984,NULL,82946,NULL),(78,30511,17,32984,NULL,82947,NULL),(78,30512,17,32984,NULL,82945,NULL),(78,30513,17,32983,NULL,82946,NULL),(78,30514,17,32983,NULL,82947,NULL),(78,30515,17,32983,NULL,82945,NULL),(78,30516,17,32986,NULL,82946,NULL),(78,30517,17,32986,NULL,82947,NULL),(78,30518,17,32986,NULL,82945,NULL),(79,30519,14,4123,NULL,10368,NULL),(79,30520,14,4122,NULL,10368,NULL),(79,30521,16,16491,NULL,41472,NULL),(79,30522,16,16491,NULL,41473,NULL),(79,30523,16,16493,NULL,41472,NULL),(79,30524,16,16493,NULL,41473,NULL),(79,30525,16,16492,NULL,41472,NULL),(79,30526,16,16492,NULL,41473,NULL),(79,30527,16,16490,NULL,41472,NULL),(79,30528,16,16490,NULL,41473,NULL),(79,30529,15,8245,NULL,20736,NULL),(79,30530,15,8246,NULL,20736,NULL),(79,30531,17,32985,NULL,82946,NULL),(79,30532,17,32985,NULL,82947,NULL),(79,30533,17,32985,NULL,82945,NULL),(79,30534,17,32982,NULL,82946,NULL),(79,30535,17,32982,NULL,82947,NULL),(79,30536,17,32982,NULL,82945,NULL),(79,30537,17,32981,NULL,82946,NULL),(79,30538,17,32981,NULL,82947,NULL),(79,30539,17,32981,NULL,82945,NULL),(79,30540,17,32984,NULL,82946,NULL),(79,30541,17,32984,NULL,82947,NULL),(79,30542,17,32984,NULL,82945,NULL),(79,30543,17,32983,NULL,82946,NULL),(79,30544,17,32983,NULL,82947,NULL),(79,30545,17,32983,NULL,82945,NULL),(79,30546,17,32986,NULL,82946,NULL),(79,30547,17,32986,NULL,82947,NULL),(79,30548,17,32986,NULL,82945,NULL),(80,30549,14,4123,NULL,10368,NULL),(80,30550,14,4122,NULL,10368,NULL),(80,30551,16,16491,NULL,41472,NULL),(80,30552,16,16491,NULL,41473,NULL),(80,30553,16,16493,NULL,41472,NULL),(80,30554,16,16493,NULL,41473,NULL),(80,30555,16,16492,NULL,41472,NULL),(80,30556,16,16492,NULL,41473,NULL),(80,30557,16,16490,NULL,41472,NULL),(80,30558,16,16490,NULL,41473,NULL),(80,30559,15,8245,NULL,20736,NULL),(80,30560,15,8246,NULL,20736,NULL),(80,30561,17,32985,NULL,82946,NULL),(80,30562,17,32985,NULL,82947,NULL),(80,30563,17,32985,NULL,82945,NULL),(80,30564,17,32982,NULL,82946,NULL),(80,30565,17,32982,NULL,82947,NULL),(80,30566,17,32982,NULL,82945,NULL),(80,30567,17,32981,NULL,82946,NULL),(80,30568,17,32981,NULL,82947,NULL),(80,30569,17,32981,NULL,82945,NULL),(80,30570,17,32984,NULL,82946,NULL),(80,30571,17,32984,NULL,82947,NULL),(80,30572,17,32984,NULL,82945,NULL),(80,30573,17,32983,NULL,82946,NULL),(80,30574,17,32983,NULL,82947,NULL),(80,30575,17,32983,NULL,82945,NULL),(80,30576,17,32986,NULL,82946,NULL),(80,30577,17,32986,NULL,82947,NULL),(80,30578,17,32986,NULL,82945,NULL),(81,30579,14,8260,NULL,12110,NULL),(81,30580,14,8260,NULL,12108,NULL),(81,30581,14,8260,NULL,12109,NULL),(81,30582,14,8261,NULL,12110,NULL),(81,30583,14,8261,NULL,12108,NULL),(81,30584,14,8261,NULL,12109,NULL),(81,30585,13,4130,NULL,6054,NULL),(81,30586,13,4130,NULL,6055,NULL),(82,30587,14,4123,NULL,10368,NULL),(82,30588,14,4122,NULL,10368,NULL),(82,30589,16,16491,NULL,41472,NULL),(82,30590,16,16491,NULL,41473,NULL),(82,30591,16,16493,NULL,41472,NULL),(82,30592,16,16493,NULL,41473,NULL),(82,30593,16,16492,NULL,41472,NULL),(82,30594,16,16492,NULL,41473,NULL),(82,30595,16,16490,NULL,41472,NULL),(82,30596,16,16490,NULL,41473,NULL),(82,30597,15,8245,NULL,20736,NULL),(82,30598,15,8246,NULL,20736,NULL),(82,30599,17,32985,NULL,82946,NULL),(82,30600,17,32985,NULL,82947,NULL),(82,30601,17,32985,NULL,82945,NULL),(82,30602,17,32982,NULL,82946,NULL),(82,30603,17,32982,NULL,82947,NULL),(82,30604,17,32982,NULL,82945,NULL),(82,30605,17,32981,NULL,82946,NULL),(82,30606,17,32981,NULL,82947,NULL),(82,30607,17,32981,NULL,82945,NULL),(82,30608,17,32984,NULL,82946,NULL),(82,30609,17,32984,NULL,82947,NULL),(82,30610,17,32984,NULL,82945,NULL),(82,30611,17,32983,NULL,82946,NULL),(82,30612,17,32983,NULL,82947,NULL),(82,30613,17,32983,NULL,82945,NULL),(82,30614,17,32986,NULL,82946,NULL),(82,30615,17,32986,NULL,82947,NULL),(82,30616,17,32986,NULL,82945,NULL),(83,30617,14,8260,NULL,12110,NULL),(83,30618,14,8260,NULL,12108,NULL),(83,30619,14,8260,NULL,12109,NULL),(83,30620,14,8261,NULL,12110,NULL),(83,30621,14,8261,NULL,12108,NULL),(83,30622,14,8261,NULL,12109,NULL),(83,30623,13,4130,NULL,6054,NULL),(83,30624,13,4130,NULL,6055,NULL),(85,30625,14,4123,NULL,10368,NULL),(85,30626,14,4122,NULL,10368,NULL),(85,30627,16,16491,NULL,41472,NULL),(85,30628,16,16491,NULL,41473,NULL),(85,30629,16,16493,NULL,41472,NULL),(85,30630,16,16493,NULL,41473,NULL),(85,30631,16,16492,NULL,41472,NULL),(85,30632,16,16492,NULL,41473,NULL),(85,30633,16,16490,NULL,41472,NULL),(85,30634,16,16490,NULL,41473,NULL),(85,30635,15,8245,NULL,20736,NULL),(85,30636,15,8246,NULL,20736,NULL),(85,30637,17,32985,NULL,82946,NULL),(85,30638,17,32985,NULL,82947,NULL),(85,30639,17,32985,NULL,82945,NULL),(85,30640,17,32982,NULL,82946,NULL),(85,30641,17,32982,NULL,82947,NULL),(85,30642,17,32982,NULL,82945,NULL),(85,30643,17,32981,NULL,82946,NULL),(85,30644,17,32981,NULL,82947,NULL),(85,30645,17,32981,NULL,82945,NULL),(85,30646,17,32984,NULL,82946,NULL),(85,30647,17,32984,NULL,82947,NULL),(85,30648,17,32984,NULL,82945,NULL),(85,30649,17,32983,NULL,82946,NULL),(85,30650,17,32983,NULL,82947,NULL),(85,30651,17,32983,NULL,82945,NULL),(85,30652,17,32986,NULL,82946,NULL),(85,30653,17,32986,NULL,82947,NULL),(85,30654,17,32986,NULL,82945,NULL),(86,30655,14,4123,NULL,10368,NULL),(86,30656,14,4122,NULL,10368,NULL),(86,30657,16,16491,NULL,41472,NULL),(86,30658,16,16491,NULL,41473,NULL),(86,30659,16,16493,NULL,41472,NULL),(86,30660,16,16493,NULL,41473,NULL),(86,30661,16,16492,NULL,41472,NULL),(86,30662,16,16492,NULL,41473,NULL),(86,30663,16,16490,NULL,41472,NULL),(86,30664,16,16490,NULL,41473,NULL),(86,30665,15,8245,NULL,20736,NULL),(86,30666,15,8246,NULL,20736,NULL),(86,30667,17,32985,NULL,82946,NULL),(86,30668,17,32985,NULL,82947,NULL),(86,30669,17,32985,NULL,82945,NULL),(86,30670,17,32982,NULL,82946,NULL),(86,30671,17,32982,NULL,82947,NULL),(86,30672,17,32982,NULL,82945,NULL),(86,30673,17,32981,NULL,82946,NULL),(86,30674,17,32981,NULL,82947,NULL),(86,30675,17,32981,NULL,82945,NULL),(86,30676,17,32984,NULL,82946,NULL),(86,30677,17,32984,NULL,82947,NULL),(86,30678,17,32984,NULL,82945,NULL),(86,30679,17,32983,NULL,82946,NULL),(86,30680,17,32983,NULL,82947,NULL),(86,30681,17,32983,NULL,82945,NULL),(86,30682,17,32986,NULL,82946,NULL),(86,30683,17,32986,NULL,82947,NULL),(86,30684,17,32986,NULL,82945,NULL),(87,30685,14,4123,NULL,10368,NULL),(87,30686,14,4122,NULL,10368,NULL),(87,30687,16,16491,NULL,41472,NULL),(87,30688,16,16491,NULL,41473,NULL),(87,30689,16,16493,NULL,41472,NULL),(87,30690,16,16493,NULL,41473,NULL),(87,30691,16,16492,NULL,41472,NULL),(87,30692,16,16492,NULL,41473,NULL),(87,30693,16,16490,NULL,41472,NULL),(87,30694,16,16490,NULL,41473,NULL),(87,30695,15,8245,NULL,20736,NULL),(87,30696,15,8246,NULL,20736,NULL),(87,30697,17,32985,NULL,82946,NULL),(87,30698,17,32985,NULL,82947,NULL),(87,30699,17,32985,NULL,82945,NULL),(87,30700,17,32982,NULL,82946,NULL),(87,30701,17,32982,NULL,82947,NULL),(87,30702,17,32982,NULL,82945,NULL),(87,30703,17,32981,NULL,82946,NULL),(87,30704,17,32981,NULL,82947,NULL),(87,30705,17,32981,NULL,82945,NULL),(87,30706,17,32984,NULL,82946,NULL),(87,30707,17,32984,NULL,82947,NULL),(87,30708,17,32984,NULL,82945,NULL),(87,30709,17,32983,NULL,82946,NULL),(87,30710,17,32983,NULL,82947,NULL),(87,30711,17,32983,NULL,82945,NULL),(87,30712,17,32986,NULL,82946,NULL),(87,30713,17,32986,NULL,82947,NULL),(87,30714,17,32986,NULL,82945,NULL),(88,30715,14,4123,NULL,10368,NULL),(88,30716,14,4122,NULL,10368,NULL),(88,30717,16,16491,NULL,41472,NULL),(88,30718,16,16491,NULL,41473,NULL),(88,30719,16,16493,NULL,41472,NULL),(88,30720,16,16493,NULL,41473,NULL),(88,30721,16,16492,NULL,41472,NULL),(88,30722,16,16492,NULL,41473,NULL),(88,30723,16,16490,NULL,41472,NULL),(88,30724,16,16490,NULL,41473,NULL),(88,30725,15,8245,NULL,20736,NULL),(88,30726,15,8246,NULL,20736,NULL),(88,30727,17,32985,NULL,82946,NULL),(88,30728,17,32985,NULL,82947,NULL),(88,30729,17,32985,NULL,82945,NULL),(88,30730,17,32982,NULL,82946,NULL),(88,30731,17,32982,NULL,82947,NULL),(88,30732,17,32982,NULL,82945,NULL),(88,30733,17,32981,NULL,82946,NULL),(88,30734,17,32981,NULL,82947,NULL),(88,30735,17,32981,NULL,82945,NULL),(88,30736,17,32984,NULL,82946,NULL),(88,30737,17,32984,NULL,82947,NULL),(88,30738,17,32984,NULL,82945,NULL),(88,30739,17,32983,NULL,82946,NULL),(88,30740,17,32983,NULL,82947,NULL),(88,30741,17,32983,NULL,82945,NULL),(88,30742,17,32986,NULL,82946,NULL),(88,30743,17,32986,NULL,82947,NULL),(88,30744,17,32986,NULL,82945,NULL),(89,30745,14,4123,NULL,10368,NULL),(89,30746,14,4122,NULL,10368,NULL),(89,30747,16,16491,NULL,41472,NULL),(89,30748,16,16491,NULL,41473,NULL),(89,30749,16,16493,NULL,41472,NULL),(89,30750,16,16493,NULL,41473,NULL),(89,30751,16,16492,NULL,41472,NULL),(89,30752,16,16492,NULL,41473,NULL),(89,30753,16,16490,NULL,41472,NULL),(89,30754,16,16490,NULL,41473,NULL),(89,30755,15,8245,NULL,20736,NULL),(89,30756,15,8246,NULL,20736,NULL),(89,30757,17,32985,NULL,82946,NULL),(89,30758,17,32985,NULL,82947,NULL),(89,30759,17,32985,NULL,82945,NULL),(89,30760,17,32982,NULL,82946,NULL),(89,30761,17,32982,NULL,82947,NULL),(89,30762,17,32982,NULL,82945,NULL),(89,30763,17,32981,NULL,82946,NULL),(89,30764,17,32981,NULL,82947,NULL),(89,30765,17,32981,NULL,82945,NULL),(89,30766,17,32984,NULL,82946,NULL),(89,30767,17,32984,NULL,82947,NULL),(89,30768,17,32984,NULL,82945,NULL),(89,30769,17,32983,NULL,82946,NULL),(89,30770,17,32983,NULL,82947,NULL),(89,30771,17,32983,NULL,82945,NULL),(89,30772,17,32986,NULL,82946,NULL),(89,30773,17,32986,NULL,82947,NULL),(89,30774,17,32986,NULL,82945,NULL),(90,30775,14,4123,NULL,10368,NULL),(90,30776,14,4122,NULL,10368,NULL),(90,30777,16,16491,NULL,41472,NULL),(90,30778,16,16491,NULL,41473,NULL),(90,30779,16,16493,NULL,41472,NULL),(90,30780,16,16493,NULL,41473,NULL),(90,30781,16,16492,NULL,41472,NULL),(90,30782,16,16492,NULL,41473,NULL),(90,30783,16,16490,NULL,41472,NULL),(90,30784,16,16490,NULL,41473,NULL),(90,30785,15,8245,NULL,20736,NULL),(90,30786,15,8246,NULL,20736,NULL),(90,30787,17,32985,NULL,82946,NULL),(90,30788,17,32985,NULL,82947,NULL),(90,30789,17,32985,NULL,82945,NULL),(90,30790,17,32982,NULL,82946,NULL),(90,30791,17,32982,NULL,82947,NULL),(90,30792,17,32982,NULL,82945,NULL),(90,30793,17,32981,NULL,82946,NULL),(90,30794,17,32981,NULL,82947,NULL),(90,30795,17,32981,NULL,82945,NULL),(90,30796,17,32984,NULL,82946,NULL),(90,30797,17,32984,NULL,82947,NULL),(90,30798,17,32984,NULL,82945,NULL),(90,30799,17,32983,NULL,82946,NULL),(90,30800,17,32983,NULL,82947,NULL),(90,30801,17,32983,NULL,82945,NULL),(90,30802,17,32986,NULL,82946,NULL),(90,30803,17,32986,NULL,82947,NULL),(90,30804,17,32986,NULL,82945,NULL),(98,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(99,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(105,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(106,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(107,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(108,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(0,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(109,NULL,17,32986,NULL,82945,NULL),(0,NULL,17,32986,NULL,82947,NULL),(0,NULL,17,32986,NULL,82946,NULL),(0,NULL,17,32986,NULL,82944,NULL),(0,NULL,17,32983,NULL,82945,NULL),(0,NULL,17,32983,NULL,82947,NULL),(0,NULL,17,32983,NULL,82946,NULL),(0,NULL,17,32983,NULL,82944,NULL),(0,NULL,17,32984,NULL,82945,NULL),(0,NULL,17,32984,NULL,82947,NULL),(0,NULL,17,32984,NULL,82946,NULL),(0,NULL,17,32984,NULL,82944,NULL),(0,NULL,17,32981,NULL,82945,NULL),(0,NULL,17,32981,NULL,82947,NULL),(0,NULL,17,32981,NULL,82946,NULL),(0,NULL,17,32981,NULL,82944,NULL),(0,NULL,17,32982,NULL,82945,NULL),(0,NULL,17,32982,NULL,82947,NULL),(0,NULL,17,32982,NULL,82946,NULL),(0,NULL,17,32982,NULL,82944,NULL),(0,NULL,17,32985,NULL,82945,NULL),(0,NULL,17,32985,NULL,82947,NULL),(0,NULL,17,32985,NULL,82946,NULL),(0,NULL,17,32985,NULL,82944,NULL),(0,NULL,15,8246,NULL,20736,NULL),(0,NULL,15,8245,NULL,20736,NULL),(0,NULL,16,16490,NULL,41473,NULL),(0,NULL,16,16490,NULL,41472,NULL),(0,NULL,16,16492,NULL,41473,NULL),(0,NULL,16,16492,NULL,41472,NULL),(0,NULL,16,16493,NULL,41473,NULL),(0,NULL,16,16493,NULL,41472,NULL),(0,NULL,16,16491,NULL,41473,NULL),(0,NULL,16,16491,NULL,41472,NULL),(0,NULL,14,4122,NULL,10368,NULL),(0,NULL,14,4123,NULL,10368,NULL),(110,NULL,17,32986,NULL,82945,NULL),(110,NULL,17,32986,NULL,82947,NULL),(110,NULL,17,32986,NULL,82946,NULL),(110,NULL,17,32983,NULL,82945,NULL),(110,NULL,17,32983,NULL,82947,NULL),(110,NULL,17,32983,NULL,82946,NULL),(110,NULL,17,32984,NULL,82945,NULL),(110,NULL,17,32984,NULL,82947,NULL),(110,NULL,17,32984,NULL,82946,NULL),(110,NULL,17,32981,NULL,82945,NULL),(110,NULL,17,32981,NULL,82947,NULL),(110,NULL,17,32981,NULL,82946,NULL),(110,NULL,17,32982,NULL,82945,NULL),(110,NULL,17,32982,NULL,82947,NULL),(110,NULL,17,32982,NULL,82946,NULL),(110,NULL,17,32985,NULL,82945,NULL),(110,NULL,17,32985,NULL,82947,NULL),(110,NULL,17,32985,NULL,82946,NULL),(110,NULL,15,8246,NULL,20736,NULL),(110,NULL,15,8245,NULL,20736,NULL),(110,NULL,16,16490,NULL,41473,NULL),(110,NULL,16,16490,NULL,41472,NULL),(110,NULL,16,16492,NULL,41473,NULL),(110,NULL,16,16492,NULL,41472,NULL),(110,NULL,16,16493,NULL,41473,NULL),(110,NULL,16,16493,NULL,41472,NULL),(110,NULL,16,16491,NULL,41473,NULL),(110,NULL,16,16491,NULL,41472,NULL),(110,NULL,14,4122,NULL,10368,NULL),(110,NULL,14,4123,NULL,10368,NULL),(111,NULL,17,32986,NULL,82945,NULL),(111,NULL,17,32986,NULL,82947,NULL),(111,NULL,17,32986,NULL,82946,NULL),(111,NULL,17,32983,NULL,82945,NULL),(111,NULL,17,32983,NULL,82947,NULL),(111,NULL,17,32983,NULL,82946,NULL),(111,NULL,17,32984,NULL,82945,NULL),(111,NULL,17,32984,NULL,82947,NULL),(111,NULL,17,32984,NULL,82946,NULL),(111,NULL,17,32981,NULL,82945,NULL),(111,NULL,17,32981,NULL,82947,NULL),(111,NULL,17,32981,NULL,82946,NULL),(111,NULL,17,32982,NULL,82945,NULL),(111,NULL,17,32982,NULL,82947,NULL),(111,NULL,17,32982,NULL,82946,NULL),(111,NULL,17,32985,NULL,82945,NULL),(111,NULL,17,32985,NULL,82947,NULL),(111,NULL,17,32985,NULL,82946,NULL),(111,NULL,15,8246,NULL,20736,NULL),(111,NULL,15,8245,NULL,20736,NULL),(111,NULL,16,16490,NULL,41473,NULL),(111,NULL,16,16490,NULL,41472,NULL),(111,NULL,16,16492,NULL,41473,NULL),(111,NULL,16,16492,NULL,41472,NULL),(111,NULL,16,16493,NULL,41473,NULL),(111,NULL,16,16493,NULL,41472,NULL),(111,NULL,16,16491,NULL,41473,NULL),(111,NULL,16,16491,NULL,41472,NULL),(111,NULL,14,4122,NULL,10368,NULL),(111,NULL,14,4123,NULL,10368,NULL),(112,NULL,17,32986,NULL,82945,NULL),(112,NULL,17,32986,NULL,82947,NULL),(112,NULL,17,32986,NULL,82946,NULL),(112,NULL,17,32983,NULL,82945,NULL),(112,NULL,17,32983,NULL,82947,NULL),(112,NULL,17,32983,NULL,82946,NULL),(112,NULL,17,32984,NULL,82945,NULL),(112,NULL,17,32984,NULL,82947,NULL),(112,NULL,17,32984,NULL,82946,NULL),(112,NULL,17,32981,NULL,82945,NULL),(112,NULL,17,32981,NULL,82947,NULL),(112,NULL,17,32981,NULL,82946,NULL),(112,NULL,17,32982,NULL,82945,NULL),(112,NULL,17,32982,NULL,82947,NULL),(112,NULL,17,32982,NULL,82946,NULL),(112,NULL,17,32985,NULL,82945,NULL),(112,NULL,17,32985,NULL,82947,NULL),(112,NULL,17,32985,NULL,82946,NULL),(112,NULL,15,8246,NULL,20736,NULL),(112,NULL,15,8245,NULL,20736,NULL),(112,NULL,16,16490,NULL,41473,NULL),(112,NULL,16,16490,NULL,41472,NULL),(112,NULL,16,16492,NULL,41473,NULL),(112,NULL,16,16492,NULL,41472,NULL),(112,NULL,16,16493,NULL,41473,NULL),(112,NULL,16,16493,NULL,41472,NULL),(112,NULL,16,16491,NULL,41473,NULL),(112,NULL,16,16491,NULL,41472,NULL),(112,NULL,14,4122,NULL,10368,NULL),(112,NULL,14,4123,NULL,10368,NULL),(113,NULL,17,32986,NULL,82945,NULL),(113,NULL,17,32986,NULL,82947,NULL),(113,NULL,17,32986,NULL,82946,NULL),(113,NULL,17,32983,NULL,82945,NULL),(113,NULL,17,32983,NULL,82947,NULL),(113,NULL,17,32983,NULL,82946,NULL),(113,NULL,17,32984,NULL,82945,NULL),(113,NULL,17,32984,NULL,82947,NULL),(113,NULL,17,32984,NULL,82946,NULL),(113,NULL,17,32981,NULL,82945,NULL),(113,NULL,17,32981,NULL,82947,NULL),(113,NULL,17,32981,NULL,82946,NULL),(113,NULL,17,32982,NULL,82945,NULL),(113,NULL,17,32982,NULL,82947,NULL),(113,NULL,17,32982,NULL,82946,NULL),(113,NULL,17,32985,NULL,82945,NULL),(113,NULL,17,32985,NULL,82947,NULL),(113,NULL,17,32985,NULL,82946,NULL),(113,NULL,15,8246,NULL,20736,NULL),(113,NULL,15,8245,NULL,20736,NULL),(113,NULL,16,16490,NULL,41473,NULL),(113,NULL,16,16490,NULL,41472,NULL),(113,NULL,16,16492,NULL,41473,NULL),(113,NULL,16,16492,NULL,41472,NULL),(113,NULL,16,16493,NULL,41473,NULL),(113,NULL,16,16493,NULL,41472,NULL),(113,NULL,16,16491,NULL,41473,NULL),(113,NULL,16,16491,NULL,41472,NULL),(113,NULL,14,4122,NULL,10368,NULL),(113,NULL,14,4123,NULL,10368,NULL),(113,NULL,17,32986,NULL,82945,NULL),(113,NULL,17,32986,NULL,82947,NULL),(113,NULL,17,32986,NULL,82946,NULL),(113,NULL,17,32983,NULL,82945,NULL),(113,NULL,17,32983,NULL,82947,NULL),(113,NULL,17,32983,NULL,82946,NULL),(113,NULL,17,32984,NULL,82945,NULL),(113,NULL,17,32984,NULL,82947,NULL),(113,NULL,17,32984,NULL,82946,NULL),(113,NULL,17,32981,NULL,82945,NULL),(113,NULL,17,32981,NULL,82947,NULL),(113,NULL,17,32981,NULL,82946,NULL),(113,NULL,17,32982,NULL,82945,NULL),(113,NULL,17,32982,NULL,82947,NULL),(113,NULL,17,32982,NULL,82946,NULL),(113,NULL,17,32985,NULL,82945,NULL),(113,NULL,17,32985,NULL,82947,NULL),(113,NULL,17,32985,NULL,82946,NULL),(113,NULL,15,8246,NULL,20736,NULL),(113,NULL,15,8245,NULL,20736,NULL),(113,NULL,16,16490,NULL,41473,NULL),(113,NULL,16,16490,NULL,41472,NULL),(113,NULL,16,16492,NULL,41473,NULL),(113,NULL,16,16492,NULL,41472,NULL),(113,NULL,16,16493,NULL,41473,NULL),(113,NULL,16,16493,NULL,41472,NULL),(113,NULL,16,16491,NULL,41473,NULL),(113,NULL,16,16491,NULL,41472,NULL),(113,NULL,14,4122,NULL,10368,NULL),(113,NULL,14,4123,NULL,10368,NULL),(114,NULL,17,32986,NULL,82945,NULL),(114,NULL,17,32986,NULL,82947,NULL),(114,NULL,17,32986,NULL,82946,NULL),(114,NULL,17,32986,NULL,82944,NULL),(114,NULL,17,32983,NULL,82945,NULL),(114,NULL,17,32983,NULL,82947,NULL),(114,NULL,17,32983,NULL,82946,NULL),(114,NULL,17,32983,NULL,82944,NULL),(114,NULL,17,32984,NULL,82945,NULL),(114,NULL,17,32984,NULL,82947,NULL),(114,NULL,17,32984,NULL,82946,NULL),(114,NULL,17,32984,NULL,82944,NULL),(114,NULL,17,32981,NULL,82945,NULL),(114,NULL,17,32981,NULL,82947,NULL),(114,NULL,17,32981,NULL,82946,NULL),(114,NULL,17,32981,NULL,82944,NULL),(114,NULL,17,32982,NULL,82945,NULL),(114,NULL,17,32982,NULL,82947,NULL),(114,NULL,17,32982,NULL,82946,NULL),(114,NULL,17,32982,NULL,82944,NULL),(114,NULL,17,32985,NULL,82945,NULL),(114,NULL,17,32985,NULL,82947,NULL),(114,NULL,17,32985,NULL,82946,NULL),(114,NULL,17,32985,NULL,82944,NULL),(114,NULL,15,8246,NULL,20736,NULL),(114,NULL,15,8245,NULL,20736,NULL),(114,NULL,16,16490,NULL,41473,NULL),(114,NULL,16,16490,NULL,41472,NULL),(114,NULL,16,16492,NULL,41473,NULL),(114,NULL,16,16492,NULL,41472,NULL),(114,NULL,16,16493,NULL,41473,NULL),(114,NULL,16,16493,NULL,41472,NULL),(114,NULL,16,16491,NULL,41473,NULL),(114,NULL,16,16491,NULL,41472,NULL),(114,NULL,14,4122,NULL,10368,NULL),(114,NULL,14,4123,NULL,10368,NULL),(115,NULL,14,4123,NULL,10368,NULL),(115,NULL,14,4122,NULL,10368,NULL),(115,NULL,16,16491,NULL,41472,NULL),(115,NULL,16,16491,NULL,41473,NULL),(115,NULL,16,16493,NULL,41472,NULL),(115,NULL,16,16493,NULL,41473,NULL),(115,NULL,16,16492,NULL,41472,NULL),(115,NULL,16,16492,NULL,41473,NULL),(115,NULL,16,16490,NULL,41472,NULL),(115,NULL,16,16490,NULL,41473,NULL),(115,NULL,15,8245,NULL,20736,NULL),(115,NULL,15,8246,NULL,20736,NULL),(115,NULL,17,32985,NULL,82946,NULL),(115,NULL,17,32985,NULL,82947,NULL),(115,NULL,17,32985,NULL,82945,NULL),(115,NULL,17,32982,NULL,82946,NULL),(115,NULL,17,32982,NULL,82947,NULL),(115,NULL,17,32982,NULL,82945,NULL),(115,NULL,17,32981,NULL,82946,NULL),(115,NULL,17,32981,NULL,82947,NULL),(115,NULL,17,32981,NULL,82945,NULL),(115,NULL,17,32984,NULL,82946,NULL),(115,NULL,17,32984,NULL,82947,NULL),(115,NULL,17,32984,NULL,82945,NULL),(115,NULL,17,32983,NULL,82946,NULL),(115,NULL,17,32983,NULL,82947,NULL),(115,NULL,17,32983,NULL,82945,NULL),(115,NULL,17,32986,NULL,82946,NULL),(115,NULL,17,32986,NULL,82947,NULL),(115,NULL,17,32986,NULL,82945,NULL),(116,NULL,14,4123,NULL,10368,NULL),(116,NULL,14,4122,NULL,10368,NULL),(116,NULL,16,16491,NULL,41472,NULL),(116,NULL,16,16491,NULL,41473,NULL),(116,NULL,16,16493,NULL,41472,NULL),(116,NULL,16,16493,NULL,41473,NULL),(116,NULL,16,16492,NULL,41472,NULL),(116,NULL,16,16492,NULL,41473,NULL),(116,NULL,16,16490,NULL,41472,NULL),(116,NULL,16,16490,NULL,41473,NULL),(116,NULL,15,8245,NULL,20736,NULL),(116,NULL,15,8246,NULL,20736,NULL),(116,NULL,17,32985,NULL,82946,NULL),(116,NULL,17,32985,NULL,82947,NULL),(116,NULL,17,32985,NULL,82945,NULL),(116,NULL,17,32982,NULL,82946,NULL),(116,NULL,17,32982,NULL,82947,NULL),(116,NULL,17,32982,NULL,82945,NULL),(116,NULL,17,32981,NULL,82946,NULL),(116,NULL,17,32981,NULL,82947,NULL),(116,NULL,17,32981,NULL,82945,NULL),(116,NULL,17,32984,NULL,82946,NULL),(116,NULL,17,32984,NULL,82947,NULL),(116,NULL,17,32984,NULL,82945,NULL),(116,NULL,17,32983,NULL,82946,NULL),(116,NULL,17,32983,NULL,82947,NULL),(116,NULL,17,32983,NULL,82945,NULL),(116,NULL,17,32986,NULL,82946,NULL),(116,NULL,17,32986,NULL,82947,NULL),(116,NULL,17,32986,NULL,82945,NULL),(117,NULL,14,4123,NULL,10368,NULL),(117,NULL,14,4122,NULL,10368,NULL),(117,NULL,16,16491,NULL,41472,NULL),(117,NULL,16,16491,NULL,41473,NULL),(117,NULL,16,16493,NULL,41472,NULL),(117,NULL,16,16493,NULL,41473,NULL),(117,NULL,16,16492,NULL,41472,NULL),(117,NULL,16,16492,NULL,41473,NULL),(117,NULL,16,16490,NULL,41472,NULL),(117,NULL,16,16490,NULL,41473,NULL),(117,NULL,15,8245,NULL,20736,NULL),(117,NULL,15,8246,NULL,20736,NULL),(117,NULL,17,32985,NULL,82946,NULL),(117,NULL,17,32985,NULL,82947,NULL),(117,NULL,17,32985,NULL,82945,NULL),(117,NULL,17,32982,NULL,82946,NULL),(117,NULL,17,32982,NULL,82947,NULL),(117,NULL,17,32982,NULL,82945,NULL),(117,NULL,17,32981,NULL,82946,NULL),(117,NULL,17,32981,NULL,82947,NULL),(117,NULL,17,32981,NULL,82945,NULL),(117,NULL,17,32984,NULL,82946,NULL),(117,NULL,17,32984,NULL,82947,NULL),(117,NULL,17,32984,NULL,82945,NULL),(117,NULL,17,32983,NULL,82946,NULL),(117,NULL,17,32983,NULL,82947,NULL),(117,NULL,17,32983,NULL,82945,NULL),(117,NULL,17,32986,NULL,82946,NULL),(117,NULL,17,32986,NULL,82947,NULL),(117,NULL,17,32986,NULL,82945,NULL),(118,NULL,14,4123,NULL,10368,NULL),(118,NULL,14,4122,NULL,10368,NULL),(118,NULL,16,16491,NULL,41472,NULL),(118,NULL,16,16491,NULL,41473,NULL),(118,NULL,16,16493,NULL,41472,NULL),(118,NULL,16,16493,NULL,41473,NULL),(118,NULL,16,16492,NULL,41472,NULL),(118,NULL,16,16492,NULL,41473,NULL),(118,NULL,16,16490,NULL,41472,NULL),(118,NULL,16,16490,NULL,41473,NULL),(118,NULL,15,8245,NULL,20736,NULL),(118,NULL,15,8246,NULL,20736,NULL),(118,NULL,17,32985,NULL,82946,NULL),(118,NULL,17,32985,NULL,82947,NULL),(118,NULL,17,32985,NULL,82945,NULL),(118,NULL,17,32982,NULL,82946,NULL),(118,NULL,17,32982,NULL,82947,NULL),(118,NULL,17,32982,NULL,82945,NULL),(118,NULL,17,32981,NULL,82946,NULL),(118,NULL,17,32981,NULL,82947,NULL),(118,NULL,17,32981,NULL,82945,NULL),(118,NULL,17,32984,NULL,82946,NULL),(118,NULL,17,32984,NULL,82947,NULL),(118,NULL,17,32984,NULL,82945,NULL),(118,NULL,17,32983,NULL,82946,NULL),(118,NULL,17,32983,NULL,82947,NULL),(118,NULL,17,32983,NULL,82945,NULL),(118,NULL,17,32986,NULL,82946,NULL),(118,NULL,17,32986,NULL,82947,NULL),(118,NULL,17,32986,NULL,82945,NULL),(119,NULL,14,4123,NULL,10368,NULL),(119,NULL,14,4122,NULL,10368,NULL),(119,NULL,16,16491,NULL,41472,NULL),(119,NULL,16,16491,NULL,41473,NULL),(119,NULL,16,16493,NULL,41472,NULL),(119,NULL,16,16493,NULL,41473,NULL),(119,NULL,16,16492,NULL,41472,NULL),(119,NULL,16,16492,NULL,41473,NULL),(119,NULL,16,16490,NULL,41472,NULL),(119,NULL,16,16490,NULL,41473,NULL),(119,NULL,15,8245,NULL,20736,NULL),(119,NULL,15,8246,NULL,20736,NULL),(119,NULL,17,32985,NULL,82946,NULL),(119,NULL,17,32985,NULL,82947,NULL),(119,NULL,17,32985,NULL,82945,NULL),(119,NULL,17,32982,NULL,82946,NULL),(119,NULL,17,32982,NULL,82947,NULL),(119,NULL,17,32982,NULL,82945,NULL),(119,NULL,17,32981,NULL,82946,NULL),(119,NULL,17,32981,NULL,82947,NULL),(119,NULL,17,32981,NULL,82945,NULL),(119,NULL,17,32984,NULL,82946,NULL),(119,NULL,17,32984,NULL,82947,NULL),(119,NULL,17,32984,NULL,82945,NULL),(119,NULL,17,32983,NULL,82946,NULL),(119,NULL,17,32983,NULL,82947,NULL),(119,NULL,17,32983,NULL,82945,NULL),(119,NULL,17,32986,NULL,82946,NULL),(119,NULL,17,32986,NULL,82947,NULL),(119,NULL,17,32986,NULL,82945,NULL),(122,30362,17,32986,NULL,82945,NULL),(122,30361,17,32986,NULL,82947,NULL),(122,30360,17,32986,NULL,82946,NULL),(122,30359,17,32983,NULL,82945,NULL),(122,30358,17,32983,NULL,82947,NULL),(122,30357,17,32983,NULL,82946,NULL),(122,30356,17,32984,NULL,82945,NULL),(122,30355,17,32984,NULL,82947,NULL),(122,30354,17,32984,NULL,82946,NULL),(122,30353,17,32981,NULL,82945,NULL),(122,30352,17,32981,NULL,82947,NULL),(122,30351,17,32981,NULL,82946,NULL),(122,30350,17,32982,NULL,82945,NULL),(122,30349,17,32982,NULL,82947,NULL),(122,30348,17,32982,NULL,82946,NULL),(122,30347,17,32985,NULL,82945,NULL),(122,30346,17,32985,NULL,82947,NULL),(122,30345,17,32985,NULL,82946,NULL),(122,30344,15,8246,NULL,20736,NULL),(122,30343,15,8245,NULL,20736,NULL),(122,30342,16,16490,NULL,41473,NULL),(122,30341,16,16490,NULL,41472,NULL),(122,30340,16,16492,NULL,41473,NULL),(122,30339,16,16492,NULL,41472,NULL),(122,30338,16,16493,NULL,41473,NULL),(122,30337,16,16493,NULL,41472,NULL),(122,30336,16,16491,NULL,41473,NULL),(122,30335,16,16491,NULL,41472,NULL),(122,30334,14,4122,NULL,10368,NULL),(122,30333,14,4123,NULL,10368,NULL),(123,28376,17,32986,NULL,82945,NULL),(123,28375,17,32986,NULL,82947,NULL),(123,28374,17,32986,NULL,82946,NULL),(123,28373,17,32983,NULL,82945,NULL),(123,28372,17,32983,NULL,82947,NULL),(123,28371,17,32983,NULL,82946,NULL),(123,28370,17,32984,NULL,82945,NULL),(123,28369,17,32984,NULL,82947,NULL),(123,28368,17,32984,NULL,82946,NULL),(123,28367,17,32981,NULL,82945,NULL),(123,28366,17,32981,NULL,82947,NULL),(123,28365,17,32981,NULL,82946,NULL),(123,28364,17,32982,NULL,82945,NULL),(123,28363,17,32982,NULL,82947,NULL),(123,28362,17,32982,NULL,82946,NULL),(123,28361,17,32985,NULL,82945,NULL),(123,28360,17,32985,NULL,82947,NULL),(123,28359,17,32985,NULL,82946,NULL),(123,28358,15,8246,NULL,20736,NULL),(123,28357,15,8245,NULL,20736,NULL),(123,28356,16,16490,NULL,41473,NULL),(123,28355,16,16490,NULL,41472,NULL),(123,28354,16,16492,NULL,41473,NULL),(123,28353,16,16492,NULL,41472,NULL),(123,28352,16,16493,NULL,41473,NULL),(123,28351,16,16493,NULL,41472,NULL),(123,28350,16,16491,NULL,41473,NULL),(123,28349,16,16491,NULL,41472,NULL),(123,28348,14,4122,NULL,10368,NULL),(123,28347,14,4123,NULL,10368,NULL),(124,29738,17,32986,NULL,82945,NULL),(124,29737,17,32986,NULL,82947,NULL),(124,29736,17,32986,NULL,82946,NULL),(124,29735,17,32983,NULL,82945,NULL),(124,29734,17,32983,NULL,82947,NULL),(124,29733,17,32983,NULL,82946,NULL),(124,29732,17,32984,NULL,82945,NULL),(124,29731,17,32984,NULL,82947,NULL),(124,29730,17,32984,NULL,82946,NULL),(124,29729,17,32981,NULL,82945,NULL),(124,29728,17,32981,NULL,82947,NULL),(124,29727,17,32981,NULL,82946,NULL),(124,29726,17,32982,NULL,82945,NULL),(124,29725,17,32982,NULL,82947,NULL),(124,29724,17,32982,NULL,82946,NULL),(124,29723,17,32985,NULL,82945,NULL),(124,29722,17,32985,NULL,82947,NULL),(124,29721,17,32985,NULL,82946,NULL),(124,29720,15,8246,NULL,20736,NULL),(124,29719,15,8245,NULL,20736,NULL),(124,29718,16,16490,NULL,41473,NULL),(124,29717,16,16490,NULL,41472,NULL),(124,29716,16,16492,NULL,41473,NULL),(124,29715,16,16492,NULL,41472,NULL),(124,29714,16,16493,NULL,41473,NULL),(124,29713,16,16493,NULL,41472,NULL),(124,29712,16,16491,NULL,41473,NULL),(124,29711,16,16491,NULL,41472,NULL),(124,29710,14,4122,NULL,10368,NULL),(124,29709,14,4123,NULL,10368,NULL),(125,28436,17,32986,NULL,82945,NULL),(125,28435,17,32986,NULL,82947,NULL),(125,28434,17,32986,NULL,82946,NULL),(125,28433,17,32983,NULL,82945,NULL),(125,28432,17,32983,NULL,82947,NULL),(125,28431,17,32983,NULL,82946,NULL),(125,28430,17,32984,NULL,82945,NULL),(125,28429,17,32984,NULL,82947,NULL),(125,28428,17,32984,NULL,82946,NULL),(125,28427,17,32981,NULL,82945,NULL),(125,28426,17,32981,NULL,82947,NULL),(125,28425,17,32981,NULL,82946,NULL),(125,28424,17,32982,NULL,82945,NULL),(125,28423,17,32982,NULL,82947,NULL),(125,28422,17,32982,NULL,82946,NULL),(125,28421,17,32985,NULL,82945,NULL),(125,28420,17,32985,NULL,82947,NULL),(125,28419,17,32985,NULL,82946,NULL),(125,28418,15,8246,NULL,20736,NULL),(125,28417,15,8245,NULL,20736,NULL),(125,28416,16,16490,NULL,41473,NULL),(125,28415,16,16490,NULL,41472,NULL),(125,28414,16,16492,NULL,41473,NULL),(125,28413,16,16492,NULL,41472,NULL),(125,28412,16,16493,NULL,41473,NULL),(125,28411,16,16493,NULL,41472,NULL),(125,28410,16,16491,NULL,41473,NULL),(125,28409,16,16491,NULL,41472,NULL),(125,28408,14,4122,NULL,10368,NULL),(125,28407,14,4123,NULL,10368,NULL),(125,28406,17,32986,NULL,82945,NULL),(125,28405,17,32986,NULL,82947,NULL),(125,28404,17,32986,NULL,82946,NULL),(125,28403,17,32983,NULL,82945,NULL),(125,28402,17,32983,NULL,82947,NULL),(125,28401,17,32983,NULL,82946,NULL),(125,28400,17,32984,NULL,82945,NULL),(125,28399,17,32984,NULL,82947,NULL),(125,28398,17,32984,NULL,82946,NULL),(125,28397,17,32981,NULL,82945,NULL),(125,28396,17,32981,NULL,82947,NULL),(125,28395,17,32981,NULL,82946,NULL),(125,28394,17,32982,NULL,82945,NULL),(125,28393,17,32982,NULL,82947,NULL),(125,28392,17,32982,NULL,82946,NULL),(125,28391,17,32985,NULL,82945,NULL),(125,28390,17,32985,NULL,82947,NULL),(125,28389,17,32985,NULL,82946,NULL),(125,28388,15,8246,NULL,20736,NULL),(125,28387,15,8245,NULL,20736,NULL),(125,28386,16,16490,NULL,41473,NULL),(125,28385,16,16490,NULL,41472,NULL),(125,28384,16,16492,NULL,41473,NULL),(125,28383,16,16492,NULL,41472,NULL),(125,28382,16,16493,NULL,41473,NULL),(125,28381,16,16493,NULL,41472,NULL),(125,28380,16,16491,NULL,41473,NULL),(125,28379,16,16491,NULL,41472,NULL),(125,28378,14,4122,NULL,10368,NULL),(125,28377,14,4123,NULL,10368,NULL),(126,30428,17,32986,NULL,82945,NULL),(126,30427,17,32986,NULL,82947,NULL),(126,30426,17,32986,NULL,82946,NULL),(126,30425,17,32986,NULL,82944,NULL),(126,30424,17,32983,NULL,82945,NULL),(126,30423,17,32983,NULL,82947,NULL),(126,30422,17,32983,NULL,82946,NULL),(126,30421,17,32983,NULL,82944,NULL),(126,30420,17,32984,NULL,82945,NULL),(126,30419,17,32984,NULL,82947,NULL),(126,30418,17,32984,NULL,82946,NULL),(126,30417,17,32984,NULL,82944,NULL),(126,30416,17,32981,NULL,82945,NULL),(126,30415,17,32981,NULL,82947,NULL),(126,30414,17,32981,NULL,82946,NULL),(126,30413,17,32981,NULL,82944,NULL),(126,30412,17,32982,NULL,82945,NULL),(126,30411,17,32982,NULL,82947,NULL),(126,30410,17,32982,NULL,82946,NULL),(126,30409,17,32982,NULL,82944,NULL),(126,30408,17,32985,NULL,82945,NULL),(126,30407,17,32985,NULL,82947,NULL),(126,30406,17,32985,NULL,82946,NULL),(126,30405,17,32985,NULL,82944,NULL),(126,30404,15,8246,NULL,20736,NULL),(126,30403,15,8245,NULL,20736,NULL),(126,30402,16,16490,NULL,41473,NULL),(126,30401,16,16490,NULL,41472,NULL),(126,30400,16,16492,NULL,41473,NULL),(126,30399,16,16492,NULL,41472,NULL),(126,30398,16,16493,NULL,41473,NULL),(126,30397,16,16493,NULL,41472,NULL),(126,30396,16,16491,NULL,41473,NULL),(126,30395,16,16491,NULL,41472,NULL),(126,30394,14,4122,NULL,10368,NULL),(126,30393,14,4123,NULL,10368,NULL),(127,30362,17,32986,NULL,82945,NULL),(127,30361,17,32986,NULL,82947,NULL),(127,30360,17,32986,NULL,82946,NULL),(127,30359,17,32983,NULL,82945,NULL),(127,30358,17,32983,NULL,82947,NULL),(127,30357,17,32983,NULL,82946,NULL),(127,30356,17,32984,NULL,82945,NULL),(127,30355,17,32984,NULL,82947,NULL),(127,30354,17,32984,NULL,82946,NULL),(127,30353,17,32981,NULL,82945,NULL),(127,30352,17,32981,NULL,82947,NULL),(127,30351,17,32981,NULL,82946,NULL),(127,30350,17,32982,NULL,82945,NULL),(127,30349,17,32982,NULL,82947,NULL),(127,30348,17,32982,NULL,82946,NULL),(127,30347,17,32985,NULL,82945,NULL),(127,30346,17,32985,NULL,82947,NULL),(127,30345,17,32985,NULL,82946,NULL),(127,30344,15,8246,NULL,20736,NULL),(127,30343,15,8245,NULL,20736,NULL),(127,30342,16,16490,NULL,41473,NULL),(127,30341,16,16490,NULL,41472,NULL),(127,30340,16,16492,NULL,41473,NULL),(127,30339,16,16492,NULL,41472,NULL),(127,30338,16,16493,NULL,41473,NULL),(127,30337,16,16493,NULL,41472,NULL),(127,30336,16,16491,NULL,41473,NULL),(127,30335,16,16491,NULL,41472,NULL),(127,30334,14,4122,NULL,10368,NULL),(127,30333,14,4123,NULL,10368,NULL),(128,28376,17,32986,NULL,82945,NULL),(128,28375,17,32986,NULL,82947,NULL),(128,28374,17,32986,NULL,82946,NULL),(128,28373,17,32983,NULL,82945,NULL),(128,28372,17,32983,NULL,82947,NULL),(128,28371,17,32983,NULL,82946,NULL),(128,28370,17,32984,NULL,82945,NULL),(128,28369,17,32984,NULL,82947,NULL),(128,28368,17,32984,NULL,82946,NULL),(128,28367,17,32981,NULL,82945,NULL),(128,28366,17,32981,NULL,82947,NULL),(128,28365,17,32981,NULL,82946,NULL),(128,28364,17,32982,NULL,82945,NULL),(128,28363,17,32982,NULL,82947,NULL),(128,28362,17,32982,NULL,82946,NULL),(128,28361,17,32985,NULL,82945,NULL),(128,28360,17,32985,NULL,82947,NULL),(128,28359,17,32985,NULL,82946,NULL),(128,28358,15,8246,NULL,20736,NULL),(128,28357,15,8245,NULL,20736,NULL),(128,28356,16,16490,NULL,41473,NULL),(128,28355,16,16490,NULL,41472,NULL),(128,28354,16,16492,NULL,41473,NULL),(128,28353,16,16492,NULL,41472,NULL),(128,28352,16,16493,NULL,41473,NULL),(128,28351,16,16493,NULL,41472,NULL),(128,28350,16,16491,NULL,41473,NULL),(128,28349,16,16491,NULL,41472,NULL),(128,28348,14,4122,NULL,10368,NULL),(128,28347,14,4123,NULL,10368,NULL),(129,29738,17,32986,NULL,82945,NULL),(129,29737,17,32986,NULL,82947,NULL),(129,29736,17,32986,NULL,82946,NULL),(129,29735,17,32983,NULL,82945,NULL),(129,29734,17,32983,NULL,82947,NULL),(129,29733,17,32983,NULL,82946,NULL),(129,29732,17,32984,NULL,82945,NULL),(129,29731,17,32984,NULL,82947,NULL),(129,29730,17,32984,NULL,82946,NULL),(129,29729,17,32981,NULL,82945,NULL),(129,29728,17,32981,NULL,82947,NULL),(129,29727,17,32981,NULL,82946,NULL),(129,29726,17,32982,NULL,82945,NULL),(129,29725,17,32982,NULL,82947,NULL),(129,29724,17,32982,NULL,82946,NULL),(129,29723,17,32985,NULL,82945,NULL),(129,29722,17,32985,NULL,82947,NULL),(129,29721,17,32985,NULL,82946,NULL),(129,29720,15,8246,NULL,20736,NULL),(129,29719,15,8245,NULL,20736,NULL),(129,29718,16,16490,NULL,41473,NULL),(129,29717,16,16490,NULL,41472,NULL),(129,29716,16,16492,NULL,41473,NULL),(129,29715,16,16492,NULL,41472,NULL),(129,29714,16,16493,NULL,41473,NULL),(129,29713,16,16493,NULL,41472,NULL),(129,29712,16,16491,NULL,41473,NULL),(129,29711,16,16491,NULL,41472,NULL),(129,29710,14,4122,NULL,10368,NULL),(129,29709,14,4123,NULL,10368,NULL),(130,28436,17,32986,NULL,82945,NULL),(130,28435,17,32986,NULL,82947,NULL),(130,28434,17,32986,NULL,82946,NULL),(130,28433,17,32983,NULL,82945,NULL),(130,28432,17,32983,NULL,82947,NULL),(130,28431,17,32983,NULL,82946,NULL),(130,28430,17,32984,NULL,82945,NULL),(130,28429,17,32984,NULL,82947,NULL),(130,28428,17,32984,NULL,82946,NULL),(130,28427,17,32981,NULL,82945,NULL),(130,28426,17,32981,NULL,82947,NULL),(130,28425,17,32981,NULL,82946,NULL),(130,28424,17,32982,NULL,82945,NULL),(130,28423,17,32982,NULL,82947,NULL),(130,28422,17,32982,NULL,82946,NULL),(130,28421,17,32985,NULL,82945,NULL),(130,28420,17,32985,NULL,82947,NULL),(130,28419,17,32985,NULL,82946,NULL),(130,28418,15,8246,NULL,20736,NULL),(130,28417,15,8245,NULL,20736,NULL),(130,28416,16,16490,NULL,41473,NULL),(130,28415,16,16490,NULL,41472,NULL),(130,28414,16,16492,NULL,41473,NULL),(130,28413,16,16492,NULL,41472,NULL),(130,28412,16,16493,NULL,41473,NULL),(130,28411,16,16493,NULL,41472,NULL),(130,28410,16,16491,NULL,41473,NULL),(130,28409,16,16491,NULL,41472,NULL),(130,28408,14,4122,NULL,10368,NULL),(130,28407,14,4123,NULL,10368,NULL),(130,28406,17,32986,NULL,82945,NULL),(130,28405,17,32986,NULL,82947,NULL),(130,28404,17,32986,NULL,82946,NULL),(130,28403,17,32983,NULL,82945,NULL),(130,28402,17,32983,NULL,82947,NULL),(130,28401,17,32983,NULL,82946,NULL),(130,28400,17,32984,NULL,82945,NULL),(130,28399,17,32984,NULL,82947,NULL),(130,28398,17,32984,NULL,82946,NULL),(130,28397,17,32981,NULL,82945,NULL),(130,28396,17,32981,NULL,82947,NULL),(130,28395,17,32981,NULL,82946,NULL),(130,28394,17,32982,NULL,82945,NULL),(130,28393,17,32982,NULL,82947,NULL),(130,28392,17,32982,NULL,82946,NULL),(130,28391,17,32985,NULL,82945,NULL),(130,28390,17,32985,NULL,82947,NULL),(130,28389,17,32985,NULL,82946,NULL),(130,28388,15,8246,NULL,20736,NULL),(130,28387,15,8245,NULL,20736,NULL),(130,28386,16,16490,NULL,41473,NULL),(130,28385,16,16490,NULL,41472,NULL),(130,28384,16,16492,NULL,41473,NULL),(130,28383,16,16492,NULL,41472,NULL),(130,28382,16,16493,NULL,41473,NULL),(130,28381,16,16493,NULL,41472,NULL),(130,28380,16,16491,NULL,41473,NULL),(130,28379,16,16491,NULL,41472,NULL),(130,28378,14,4122,NULL,10368,NULL),(130,28377,14,4123,NULL,10368,NULL),(131,30428,17,32986,NULL,82945,NULL),(131,30427,17,32986,NULL,82947,NULL),(131,30426,17,32986,NULL,82946,NULL),(131,30425,17,32986,NULL,82944,NULL),(131,30424,17,32983,NULL,82945,NULL),(131,30423,17,32983,NULL,82947,NULL),(131,30422,17,32983,NULL,82946,NULL),(131,30421,17,32983,NULL,82944,NULL),(131,30420,17,32984,NULL,82945,NULL),(131,30419,17,32984,NULL,82947,NULL),(131,30418,17,32984,NULL,82946,NULL),(131,30417,17,32984,NULL,82944,NULL),(131,30416,17,32981,NULL,82945,NULL),(131,30415,17,32981,NULL,82947,NULL),(131,30414,17,32981,NULL,82946,NULL),(131,30413,17,32981,NULL,82944,NULL),(131,30412,17,32982,NULL,82945,NULL),(131,30411,17,32982,NULL,82947,NULL),(131,30410,17,32982,NULL,82946,NULL),(131,30409,17,32982,NULL,82944,NULL),(131,30408,17,32985,NULL,82945,NULL),(131,30407,17,32985,NULL,82947,NULL),(131,30406,17,32985,NULL,82946,NULL),(131,30405,17,32985,NULL,82944,NULL),(131,30404,15,8246,NULL,20736,NULL),(131,30403,15,8245,NULL,20736,NULL),(131,30402,16,16490,NULL,41473,NULL),(131,30401,16,16490,NULL,41472,NULL),(131,30400,16,16492,NULL,41473,NULL),(131,30399,16,16492,NULL,41472,NULL),(131,30398,16,16493,NULL,41473,NULL),(131,30397,16,16493,NULL,41472,NULL),(131,30396,16,16491,NULL,41473,NULL),(131,30395,16,16491,NULL,41472,NULL),(131,30394,14,4122,NULL,10368,NULL),(131,30393,14,4123,NULL,10368,NULL),(132,99999,17,32986,NULL,82945,NULL),(132,99999,17,32986,NULL,82947,NULL),(132,99999,17,32986,NULL,82946,NULL),(132,99999,17,32983,NULL,82945,NULL),(132,99999,17,32983,NULL,82947,NULL),(132,99999,17,32983,NULL,82946,NULL),(132,99999,17,32984,NULL,82945,NULL),(132,99999,17,32984,NULL,82947,NULL),(132,99999,17,32984,NULL,82946,NULL),(132,99999,17,32981,NULL,82945,NULL),(132,99999,17,32981,NULL,82947,NULL),(132,99999,17,32981,NULL,82946,NULL),(132,99999,17,32982,NULL,82945,NULL),(132,99999,17,32982,NULL,82947,NULL),(132,99999,17,32982,NULL,82946,NULL),(132,99999,17,32985,NULL,82945,NULL),(132,99999,17,32985,NULL,82947,NULL),(132,99999,17,32985,NULL,82946,NULL),(132,99999,15,8246,NULL,20736,NULL),(132,99999,15,8245,NULL,20736,NULL),(132,99999,16,16490,NULL,41473,NULL),(132,99999,16,16490,NULL,41472,NULL),(132,99999,16,16492,NULL,41473,NULL),(132,99999,16,16492,NULL,41472,NULL),(132,99999,16,16493,NULL,41473,NULL),(132,99999,16,16493,NULL,41472,NULL),(132,99999,16,16491,NULL,41473,NULL),(132,99999,16,16491,NULL,41472,NULL),(132,99999,14,4122,NULL,10368,NULL),(132,99999,14,4123,NULL,10368,NULL),(133,99999,17,32986,NULL,82945,NULL),(133,99999,17,32986,NULL,82947,NULL),(133,99999,17,32986,NULL,82946,NULL),(133,99999,17,32983,NULL,82945,NULL),(133,99999,17,32983,NULL,82947,NULL),(133,99999,17,32983,NULL,82946,NULL),(133,99999,17,32984,NULL,82945,NULL),(133,99999,17,32984,NULL,82947,NULL),(133,99999,17,32984,NULL,82946,NULL),(133,99999,17,32981,NULL,82945,NULL),(133,99999,17,32981,NULL,82947,NULL),(133,99999,17,32981,NULL,82946,NULL),(133,99999,17,32982,NULL,82945,NULL),(133,99999,17,32982,NULL,82947,NULL),(133,99999,17,32982,NULL,82946,NULL),(133,99999,17,32985,NULL,82945,NULL),(133,99999,17,32985,NULL,82947,NULL),(133,99999,17,32985,NULL,82946,NULL),(133,99999,15,8246,NULL,20736,NULL),(133,99999,15,8245,NULL,20736,NULL),(133,99999,16,16490,NULL,41473,NULL),(133,99999,16,16490,NULL,41472,NULL),(133,99999,16,16492,NULL,41473,NULL),(133,99999,16,16492,NULL,41472,NULL),(133,99999,16,16493,NULL,41473,NULL),(133,99999,16,16493,NULL,41472,NULL),(133,99999,16,16491,NULL,41473,NULL),(133,99999,16,16491,NULL,41472,NULL),(133,99999,14,4122,NULL,10368,NULL),(133,99999,14,4123,NULL,10368,NULL),(134,99999,17,32986,NULL,82945,NULL),(134,99999,17,32986,NULL,82947,NULL),(134,99999,17,32986,NULL,82946,NULL),(134,99999,17,32983,NULL,82945,NULL),(134,99999,17,32983,NULL,82947,NULL),(134,99999,17,32983,NULL,82946,NULL),(134,99999,17,32984,NULL,82945,NULL),(134,99999,17,32984,NULL,82947,NULL),(134,99999,17,32984,NULL,82946,NULL),(134,99999,17,32981,NULL,82945,NULL),(134,99999,17,32981,NULL,82947,NULL),(134,99999,17,32981,NULL,82946,NULL),(134,99999,17,32982,NULL,82945,NULL),(134,99999,17,32982,NULL,82947,NULL),(134,99999,17,32982,NULL,82946,NULL),(134,99999,17,32985,NULL,82945,NULL),(134,99999,17,32985,NULL,82947,NULL),(134,99999,17,32985,NULL,82946,NULL),(134,99999,15,8246,NULL,20736,NULL),(134,99999,15,8245,NULL,20736,NULL),(134,99999,16,16490,NULL,41473,NULL),(134,99999,16,16490,NULL,41472,NULL),(134,99999,16,16492,NULL,41473,NULL),(134,99999,16,16492,NULL,41472,NULL),(134,99999,16,16493,NULL,41473,NULL),(134,99999,16,16493,NULL,41472,NULL),(134,99999,16,16491,NULL,41473,NULL),(134,99999,16,16491,NULL,41472,NULL),(134,99999,14,4122,NULL,10368,NULL),(134,99999,14,4123,NULL,10368,NULL),(135,99999,17,32986,NULL,82945,NULL),(135,99999,17,32986,NULL,82947,NULL),(135,99999,17,32986,NULL,82946,NULL),(135,99999,17,32983,NULL,82945,NULL),(135,99999,17,32983,NULL,82947,NULL),(135,99999,17,32983,NULL,82946,NULL),(135,99999,17,32984,NULL,82945,NULL),(135,99999,17,32984,NULL,82947,NULL),(135,99999,17,32984,NULL,82946,NULL),(135,99999,17,32981,NULL,82945,NULL),(135,99999,17,32981,NULL,82947,NULL),(135,99999,17,32981,NULL,82946,NULL),(135,99999,17,32982,NULL,82945,NULL),(135,99999,17,32982,NULL,82947,NULL),(135,99999,17,32982,NULL,82946,NULL),(135,99999,17,32985,NULL,82945,NULL),(135,99999,17,32985,NULL,82947,NULL),(135,99999,17,32985,NULL,82946,NULL),(135,99999,15,8246,NULL,20736,NULL),(135,99999,15,8245,NULL,20736,NULL),(135,99999,16,16490,NULL,41473,NULL),(135,99999,16,16490,NULL,41472,NULL),(135,99999,16,16492,NULL,41473,NULL),(135,99999,16,16492,NULL,41472,NULL),(135,99999,16,16493,NULL,41473,NULL),(135,99999,16,16493,NULL,41472,NULL),(135,99999,16,16491,NULL,41473,NULL),(135,99999,16,16491,NULL,41472,NULL),(135,99999,14,4122,NULL,10368,NULL),(135,99999,14,4123,NULL,10368,NULL),(135,99999,17,32986,NULL,82945,NULL),(135,99999,17,32986,NULL,82947,NULL),(135,99999,17,32986,NULL,82946,NULL),(135,99999,17,32983,NULL,82945,NULL),(135,99999,17,32983,NULL,82947,NULL),(135,99999,17,32983,NULL,82946,NULL),(135,99999,17,32984,NULL,82945,NULL),(135,99999,17,32984,NULL,82947,NULL),(135,99999,17,32984,NULL,82946,NULL),(135,99999,17,32981,NULL,82945,NULL),(135,99999,17,32981,NULL,82947,NULL),(135,99999,17,32981,NULL,82946,NULL),(135,99999,17,32982,NULL,82945,NULL),(135,99999,17,32982,NULL,82947,NULL),(135,99999,17,32982,NULL,82946,NULL),(135,99999,17,32985,NULL,82945,NULL),(135,99999,17,32985,NULL,82947,NULL),(135,99999,17,32985,NULL,82946,NULL),(135,99999,15,8246,NULL,20736,NULL),(135,99999,15,8245,NULL,20736,NULL),(135,99999,16,16490,NULL,41473,NULL),(135,99999,16,16490,NULL,41472,NULL),(135,99999,16,16492,NULL,41473,NULL),(135,99999,16,16492,NULL,41472,NULL),(135,99999,16,16493,NULL,41473,NULL),(135,99999,16,16493,NULL,41472,NULL),(135,99999,16,16491,NULL,41473,NULL),(135,99999,16,16491,NULL,41472,NULL),(135,99999,14,4122,NULL,10368,NULL),(135,99999,14,4123,NULL,10368,NULL),(136,99999,17,32986,NULL,82945,NULL),(136,99999,17,32986,NULL,82947,NULL),(136,99999,17,32986,NULL,82946,NULL),(136,99999,17,32986,NULL,82944,NULL),(136,99999,17,32983,NULL,82945,NULL),(136,99999,17,32983,NULL,82947,NULL),(136,99999,17,32983,NULL,82946,NULL),(136,99999,17,32983,NULL,82944,NULL),(136,99999,17,32984,NULL,82945,NULL),(136,99999,17,32984,NULL,82947,NULL),(136,99999,17,32984,NULL,82946,NULL),(136,99999,17,32984,NULL,82944,NULL),(136,99999,17,32981,NULL,82945,NULL),(136,99999,17,32981,NULL,82947,NULL),(136,99999,17,32981,NULL,82946,NULL),(136,99999,17,32981,NULL,82944,NULL),(136,99999,17,32982,NULL,82945,NULL),(136,99999,17,32982,NULL,82947,NULL),(136,99999,17,32982,NULL,82946,NULL),(136,99999,17,32982,NULL,82944,NULL),(136,99999,17,32985,NULL,82945,NULL),(136,99999,17,32985,NULL,82947,NULL),(136,99999,17,32985,NULL,82946,NULL),(136,99999,17,32985,NULL,82944,NULL),(136,99999,15,8246,NULL,20736,NULL),(136,99999,15,8245,NULL,20736,NULL),(136,99999,16,16490,NULL,41473,NULL),(136,99999,16,16490,NULL,41472,NULL),(136,99999,16,16492,NULL,41473,NULL),(136,99999,16,16492,NULL,41472,NULL),(136,99999,16,16493,NULL,41473,NULL),(136,99999,16,16493,NULL,41472,NULL),(136,99999,16,16491,NULL,41473,NULL),(136,99999,16,16491,NULL,41472,NULL),(136,99999,14,4122,NULL,10368,NULL),(136,99999,14,4123,NULL,10368,NULL),(137,37345,17,32986,NULL,82945,NULL),(137,37346,17,32986,NULL,82947,NULL),(137,37347,17,32986,NULL,82946,NULL),(137,37348,17,32983,NULL,82945,NULL),(137,37349,17,32983,NULL,82947,NULL),(137,37350,17,32983,NULL,82946,NULL),(137,37351,17,32984,NULL,82945,NULL),(137,37352,17,32984,NULL,82947,NULL),(137,37353,17,32984,NULL,82946,NULL),(137,37354,17,32981,NULL,82945,NULL),(137,37355,17,32981,NULL,82947,NULL),(137,37356,17,32981,NULL,82946,NULL),(137,37357,17,32982,NULL,82945,NULL),(137,37358,17,32982,NULL,82947,NULL),(137,37359,17,32982,NULL,82946,NULL),(137,37360,17,32985,NULL,82945,NULL),(137,37361,17,32985,NULL,82947,NULL),(137,37362,17,32985,NULL,82946,NULL),(137,37363,15,8246,NULL,20736,NULL),(137,37364,15,8245,NULL,20736,NULL),(137,37365,16,16490,NULL,41473,NULL),(137,37366,16,16490,NULL,41472,NULL),(137,37367,16,16492,NULL,41473,NULL),(137,37368,16,16492,NULL,41472,NULL),(137,37369,16,16493,NULL,41473,NULL),(137,37370,16,16493,NULL,41472,NULL),(137,37371,16,16491,NULL,41473,NULL),(137,37372,16,16491,NULL,41472,NULL),(137,37373,14,4122,NULL,10368,NULL),(137,37374,14,4123,NULL,10368,NULL),(138,36989,17,32986,NULL,82945,NULL),(138,36988,17,32986,NULL,82947,NULL),(138,36987,17,32986,NULL,82946,NULL),(138,36986,17,32983,NULL,82945,NULL),(138,36985,17,32983,NULL,82947,NULL),(138,36984,17,32983,NULL,82946,NULL),(138,36983,17,32984,NULL,82945,NULL),(138,36982,17,32984,NULL,82947,NULL),(138,36981,17,32984,NULL,82946,NULL),(138,36980,17,32981,NULL,82945,NULL),(138,36979,17,32981,NULL,82947,NULL),(138,36978,17,32981,NULL,82946,NULL),(138,36977,17,32982,NULL,82945,NULL),(138,36976,17,32982,NULL,82947,NULL),(138,36975,17,32982,NULL,82946,NULL),(138,36974,17,32985,NULL,82945,NULL),(138,36973,17,32985,NULL,82947,NULL),(138,36972,17,32985,NULL,82946,NULL),(138,36971,15,8246,NULL,20736,NULL),(138,36970,15,8245,NULL,20736,NULL),(138,36969,16,16490,NULL,41473,NULL),(138,36968,16,16490,NULL,41472,NULL),(138,36967,16,16492,NULL,41473,NULL),(138,36966,16,16492,NULL,41472,NULL),(138,36965,16,16493,NULL,41473,NULL),(138,36964,16,16493,NULL,41472,NULL),(138,36963,16,16491,NULL,41473,NULL),(138,36962,16,16491,NULL,41472,NULL),(138,36961,14,4122,NULL,10368,NULL),(138,36960,14,4123,NULL,10368,NULL),(139,37218,17,32986,NULL,82945,NULL),(139,37217,17,32986,NULL,82947,NULL),(139,37216,17,32986,NULL,82946,NULL),(139,37215,17,32983,NULL,82945,NULL),(139,37214,17,32983,NULL,82947,NULL),(139,37213,17,32983,NULL,82946,NULL),(139,37212,17,32984,NULL,82945,NULL),(139,37211,17,32984,NULL,82947,NULL),(139,37210,17,32984,NULL,82946,NULL),(139,37209,17,32981,NULL,82945,NULL),(139,37208,17,32981,NULL,82947,NULL),(139,37207,17,32981,NULL,82946,NULL),(139,37206,17,32982,NULL,82945,NULL),(139,37205,17,32982,NULL,82947,NULL),(139,37204,17,32982,NULL,82946,NULL),(139,37203,17,32985,NULL,82945,NULL),(139,37202,17,32985,NULL,82947,NULL),(139,37201,17,32985,NULL,82946,NULL),(139,37200,15,8246,NULL,20736,NULL),(139,37199,15,8245,NULL,20736,NULL),(139,37198,16,16490,NULL,41473,NULL),(139,37197,16,16490,NULL,41472,NULL),(139,37196,16,16492,NULL,41473,NULL),(139,37195,16,16492,NULL,41472,NULL),(139,37194,16,16493,NULL,41473,NULL),(139,37193,16,16493,NULL,41472,NULL),(139,37192,16,16491,NULL,41473,NULL),(139,37191,16,16491,NULL,41472,NULL),(139,37190,14,4122,NULL,10368,NULL),(139,37189,14,4123,NULL,10368,NULL),(140,37049,17,32986,NULL,82945,NULL),(140,37048,17,32986,NULL,82947,NULL),(140,37047,17,32986,NULL,82946,NULL),(140,37046,17,32983,NULL,82945,NULL),(140,37045,17,32983,NULL,82947,NULL),(140,37044,17,32983,NULL,82946,NULL),(140,37043,17,32984,NULL,82945,NULL),(140,37042,17,32984,NULL,82947,NULL),(140,37041,17,32984,NULL,82946,NULL),(140,37040,17,32981,NULL,82945,NULL),(140,37039,17,32981,NULL,82947,NULL),(140,37038,17,32981,NULL,82946,NULL),(140,37037,17,32982,NULL,82945,NULL),(140,37036,17,32982,NULL,82947,NULL),(140,37035,17,32982,NULL,82946,NULL),(140,37034,17,32985,NULL,82945,NULL),(140,37033,17,32985,NULL,82947,NULL),(140,37032,17,32985,NULL,82946,NULL),(140,37031,15,8246,NULL,20736,NULL),(140,37030,15,8245,NULL,20736,NULL),(140,37029,16,16490,NULL,41473,NULL),(140,37028,16,16490,NULL,41472,NULL),(140,37027,16,16492,NULL,41473,NULL),(140,37026,16,16492,NULL,41472,NULL),(140,37025,16,16493,NULL,41473,NULL),(140,37024,16,16493,NULL,41472,NULL),(140,37023,16,16491,NULL,41473,NULL),(140,37022,16,16491,NULL,41472,NULL),(140,37021,14,4122,NULL,10368,NULL),(140,37020,14,4123,NULL,10368,NULL),(140,37019,17,32986,NULL,82945,NULL),(140,37018,17,32986,NULL,82947,NULL),(140,37017,17,32986,NULL,82946,NULL),(140,37016,17,32983,NULL,82945,NULL),(140,37015,17,32983,NULL,82947,NULL),(140,37014,17,32983,NULL,82946,NULL),(140,37013,17,32984,NULL,82945,NULL),(140,37012,17,32984,NULL,82947,NULL),(140,37011,17,32984,NULL,82946,NULL),(140,37010,17,32981,NULL,82945,NULL),(140,37009,17,32981,NULL,82947,NULL),(140,37008,17,32981,NULL,82946,NULL),(140,37007,17,32982,NULL,82945,NULL),(140,37006,17,32982,NULL,82947,NULL),(140,37005,17,32982,NULL,82946,NULL),(140,37004,17,32985,NULL,82945,NULL),(140,37003,17,32985,NULL,82947,NULL),(140,37002,17,32985,NULL,82946,NULL),(140,37001,15,8246,NULL,20736,NULL),(140,37000,15,8245,NULL,20736,NULL),(140,36999,16,16490,NULL,41473,NULL),(140,36998,16,16490,NULL,41472,NULL),(140,36997,16,16492,NULL,41473,NULL),(140,36996,16,16492,NULL,41472,NULL),(140,36995,16,16493,NULL,41473,NULL),(140,36994,16,16493,NULL,41472,NULL),(140,36993,16,16491,NULL,41473,NULL),(140,36992,16,16491,NULL,41472,NULL),(140,36991,14,4122,NULL,10368,NULL),(140,36990,14,4123,NULL,10368,NULL),(141,37279,17,32986,NULL,82945,NULL),(141,37280,17,32986,NULL,82947,NULL),(141,37281,17,32986,NULL,82946,NULL),(141,37282,17,32986,NULL,82944,NULL),(141,37283,17,32983,NULL,82945,NULL),(141,37284,17,32983,NULL,82947,NULL),(141,37285,17,32983,NULL,82946,NULL),(141,37286,17,32983,NULL,82944,NULL),(141,37287,17,32984,NULL,82945,NULL),(141,37288,17,32984,NULL,82947,NULL),(141,37289,17,32984,NULL,82946,NULL),(141,37290,17,32984,NULL,82944,NULL),(141,37291,17,32981,NULL,82945,NULL),(141,37292,17,32981,NULL,82947,NULL),(141,37293,17,32981,NULL,82946,NULL),(141,37294,17,32981,NULL,82944,NULL),(141,37295,17,32982,NULL,82945,NULL),(141,37296,17,32982,NULL,82947,NULL),(141,37297,17,32982,NULL,82946,NULL),(141,37298,17,32982,NULL,82944,NULL),(141,37299,17,32985,NULL,82945,NULL),(141,37300,17,32985,NULL,82947,NULL),(141,37301,17,32985,NULL,82946,NULL),(141,37302,17,32985,NULL,82944,NULL),(141,37303,15,8246,NULL,20736,NULL),(141,37304,15,8245,NULL,20736,NULL),(141,37305,16,16490,NULL,41473,NULL),(141,37306,16,16490,NULL,41472,NULL),(141,37307,16,16492,NULL,41473,NULL),(141,37308,16,16492,NULL,41472,NULL),(141,37309,16,16493,NULL,41473,NULL),(141,37310,16,16493,NULL,41472,NULL),(141,37311,16,16491,NULL,41473,NULL),(141,37312,16,16491,NULL,41472,NULL),(141,37313,14,4122,NULL,10368,NULL),(141,37314,14,4123,NULL,10368,NULL),(142,37945,17,32986,NULL,82945,NULL),(142,37946,17,32986,NULL,82947,NULL),(142,37947,17,32986,NULL,82946,NULL),(142,37948,17,32983,NULL,82945,NULL),(142,37949,17,32983,NULL,82947,NULL),(142,37950,17,32983,NULL,82946,NULL),(142,37951,17,32984,NULL,82945,NULL),(142,37952,17,32984,NULL,82947,NULL),(142,37953,17,32984,NULL,82946,NULL),(142,37954,17,32981,NULL,82945,NULL),(142,37955,17,32981,NULL,82947,NULL),(142,37956,17,32981,NULL,82946,NULL),(142,37957,17,32982,NULL,82945,NULL),(142,37958,17,32982,NULL,82947,NULL),(142,37959,17,32982,NULL,82946,NULL),(142,37960,17,32985,NULL,82945,NULL),(142,37961,17,32985,NULL,82947,NULL),(142,37962,17,32985,NULL,82946,NULL),(142,37963,15,8246,NULL,20736,NULL),(142,37964,15,8245,NULL,20736,NULL),(142,37965,16,16490,NULL,41473,NULL),(142,37966,16,16490,NULL,41472,NULL),(142,37967,16,16492,NULL,41473,NULL),(142,37968,16,16492,NULL,41472,NULL),(142,37969,16,16493,NULL,41473,NULL),(142,37970,16,16493,NULL,41472,NULL),(142,37971,16,16491,NULL,41473,NULL),(142,37972,16,16491,NULL,41472,NULL),(142,37973,14,4122,NULL,10368,NULL),(142,37974,14,4123,NULL,10368,NULL),(143,37589,17,32986,NULL,82945,NULL),(143,37588,17,32986,NULL,82947,NULL),(143,37587,17,32986,NULL,82946,NULL),(143,37586,17,32983,NULL,82945,NULL),(143,37585,17,32983,NULL,82947,NULL),(143,37584,17,32983,NULL,82946,NULL),(143,37583,17,32984,NULL,82945,NULL),(143,37582,17,32984,NULL,82947,NULL),(143,37581,17,32984,NULL,82946,NULL),(143,37580,17,32981,NULL,82945,NULL),(143,37579,17,32981,NULL,82947,NULL),(143,37578,17,32981,NULL,82946,NULL),(143,37577,17,32982,NULL,82945,NULL),(143,37576,17,32982,NULL,82947,NULL),(143,37575,17,32982,NULL,82946,NULL),(143,37574,17,32985,NULL,82945,NULL),(143,37573,17,32985,NULL,82947,NULL),(143,37572,17,32985,NULL,82946,NULL),(143,37571,15,8246,NULL,20736,NULL),(143,37570,15,8245,NULL,20736,NULL),(143,37569,16,16490,NULL,41473,NULL),(143,37568,16,16490,NULL,41472,NULL),(143,37567,16,16492,NULL,41473,NULL),(143,37566,16,16492,NULL,41472,NULL),(143,37565,16,16493,NULL,41473,NULL),(143,37564,16,16493,NULL,41472,NULL),(143,37563,16,16491,NULL,41473,NULL),(143,37562,16,16491,NULL,41472,NULL),(143,37561,14,4122,NULL,10368,NULL),(143,37560,14,4123,NULL,10368,NULL),(144,37818,17,32986,NULL,82945,NULL),(144,37817,17,32986,NULL,82947,NULL),(144,37816,17,32986,NULL,82946,NULL),(144,37815,17,32983,NULL,82945,NULL),(144,37814,17,32983,NULL,82947,NULL),(144,37813,17,32983,NULL,82946,NULL),(144,37812,17,32984,NULL,82945,NULL),(144,37811,17,32984,NULL,82947,NULL),(144,37810,17,32984,NULL,82946,NULL),(144,37809,17,32981,NULL,82945,NULL),(144,37808,17,32981,NULL,82947,NULL),(144,37807,17,32981,NULL,82946,NULL),(144,37806,17,32982,NULL,82945,NULL),(144,37805,17,32982,NULL,82947,NULL),(144,37804,17,32982,NULL,82946,NULL),(144,37803,17,32985,NULL,82945,NULL),(144,37802,17,32985,NULL,82947,NULL),(144,37801,17,32985,NULL,82946,NULL),(144,37800,15,8246,NULL,20736,NULL),(144,37799,15,8245,NULL,20736,NULL),(144,37798,16,16490,NULL,41473,NULL),(144,37797,16,16490,NULL,41472,NULL),(144,37796,16,16492,NULL,41473,NULL),(144,37795,16,16492,NULL,41472,NULL),(144,37794,16,16493,NULL,41473,NULL),(144,37793,16,16493,NULL,41472,NULL),(144,37792,16,16491,NULL,41473,NULL),(144,37791,16,16491,NULL,41472,NULL),(144,37790,14,4122,NULL,10368,NULL),(144,37789,14,4123,NULL,10368,NULL),(145,37649,17,32986,NULL,82945,NULL),(145,37648,17,32986,NULL,82947,NULL),(145,37647,17,32986,NULL,82946,NULL),(145,37646,17,32983,NULL,82945,NULL),(145,37645,17,32983,NULL,82947,NULL),(145,37644,17,32983,NULL,82946,NULL),(145,37643,17,32984,NULL,82945,NULL),(145,37642,17,32984,NULL,82947,NULL),(145,37641,17,32984,NULL,82946,NULL),(145,37640,17,32981,NULL,82945,NULL),(145,37639,17,32981,NULL,82947,NULL),(145,37638,17,32981,NULL,82946,NULL),(145,37637,17,32982,NULL,82945,NULL),(145,37636,17,32982,NULL,82947,NULL),(145,37635,17,32982,NULL,82946,NULL),(145,37634,17,32985,NULL,82945,NULL),(145,37633,17,32985,NULL,82947,NULL),(145,37632,17,32985,NULL,82946,NULL),(145,37631,15,8246,NULL,20736,NULL),(145,37630,15,8245,NULL,20736,NULL),(145,37629,16,16490,NULL,41473,NULL),(145,37628,16,16490,NULL,41472,NULL),(145,37627,16,16492,NULL,41473,NULL),(145,37626,16,16492,NULL,41472,NULL),(145,37625,16,16493,NULL,41473,NULL),(145,37624,16,16493,NULL,41472,NULL),(145,37623,16,16491,NULL,41473,NULL),(145,37622,16,16491,NULL,41472,NULL),(145,37621,14,4122,NULL,10368,NULL),(145,37620,14,4123,NULL,10368,NULL),(145,37619,17,32986,NULL,82945,NULL),(145,37618,17,32986,NULL,82947,NULL),(145,37617,17,32986,NULL,82946,NULL),(145,37616,17,32983,NULL,82945,NULL),(145,37615,17,32983,NULL,82947,NULL),(145,37614,17,32983,NULL,82946,NULL),(145,37613,17,32984,NULL,82945,NULL),(145,37612,17,32984,NULL,82947,NULL),(145,37611,17,32984,NULL,82946,NULL),(145,37610,17,32981,NULL,82945,NULL),(145,37609,17,32981,NULL,82947,NULL),(145,37608,17,32981,NULL,82946,NULL),(145,37607,17,32982,NULL,82945,NULL),(145,37606,17,32982,NULL,82947,NULL),(145,37605,17,32982,NULL,82946,NULL),(145,37604,17,32985,NULL,82945,NULL),(145,37603,17,32985,NULL,82947,NULL),(145,37602,17,32985,NULL,82946,NULL),(145,37601,15,8246,NULL,20736,NULL),(145,37600,15,8245,NULL,20736,NULL),(145,37599,16,16490,NULL,41473,NULL),(145,37598,16,16490,NULL,41472,NULL),(145,37597,16,16492,NULL,41473,NULL),(145,37596,16,16492,NULL,41472,NULL),(145,37595,16,16493,NULL,41473,NULL),(145,37594,16,16493,NULL,41472,NULL),(145,37593,16,16491,NULL,41473,NULL),(145,37592,16,16491,NULL,41472,NULL),(145,37591,14,4122,NULL,10368,NULL),(145,37590,14,4123,NULL,10368,NULL),(146,37879,17,32986,NULL,82945,NULL),(146,37880,17,32986,NULL,82947,NULL),(146,37881,17,32986,NULL,82946,NULL),(146,37882,17,32986,NULL,82944,NULL),(146,37883,17,32983,NULL,82945,NULL),(146,37884,17,32983,NULL,82947,NULL),(146,37885,17,32983,NULL,82946,NULL),(146,37886,17,32983,NULL,82944,NULL),(146,37887,17,32984,NULL,82945,NULL),(146,37888,17,32984,NULL,82947,NULL),(146,37889,17,32984,NULL,82946,NULL),(146,37890,17,32984,NULL,82944,NULL),(146,37891,17,32981,NULL,82945,NULL),(146,37892,17,32981,NULL,82947,NULL),(146,37893,17,32981,NULL,82946,NULL),(146,37894,17,32981,NULL,82944,NULL),(146,37895,17,32982,NULL,82945,NULL),(146,37896,17,32982,NULL,82947,NULL),(146,37897,17,32982,NULL,82946,NULL),(146,37898,17,32982,NULL,82944,NULL),(146,37899,17,32985,NULL,82945,NULL),(146,37900,17,32985,NULL,82947,NULL),(146,37901,17,32985,NULL,82946,NULL),(146,37902,17,32985,NULL,82944,NULL),(146,37903,15,8246,NULL,20736,NULL),(146,37904,15,8245,NULL,20736,NULL),(146,37905,16,16490,NULL,41473,NULL),(146,37906,16,16490,NULL,41472,NULL),(146,37907,16,16492,NULL,41473,NULL),(146,37908,16,16492,NULL,41472,NULL),(146,37909,16,16493,NULL,41473,NULL),(146,37910,16,16493,NULL,41472,NULL),(146,37911,16,16491,NULL,41473,NULL),(146,37912,16,16491,NULL,41472,NULL),(146,37913,14,4122,NULL,10368,NULL),(146,37914,14,4123,NULL,10368,NULL),(147,41784,20,263869,NULL,663567,NULL),(147,41785,20,263869,NULL,663568,NULL),(147,41786,20,263869,NULL,663566,NULL),(147,41787,20,263869,NULL,663565,NULL),(147,41788,20,263870,NULL,663567,NULL),(147,41789,20,263870,NULL,663568,NULL),(147,41790,20,263870,NULL,663566,NULL),(147,41791,20,263870,NULL,663565,NULL),(147,41792,20,263871,NULL,663567,NULL),(147,41793,20,263871,NULL,663568,NULL),(147,41794,20,263871,NULL,663566,NULL),(147,41795,20,263871,NULL,663565,NULL),(147,41796,20,263872,NULL,663567,NULL),(147,41797,20,263872,NULL,663568,NULL),(147,41798,20,263872,NULL,663566,NULL),(147,41799,20,263872,NULL,663565,NULL),(147,41800,18,65968,NULL,165892,NULL),(147,41801,18,65968,NULL,165891,NULL),(147,41802,18,65967,NULL,165892,NULL),(147,41803,18,65967,NULL,165891,NULL),(147,41804,19,131935,NULL,331784,NULL),(147,41805,19,131935,NULL,331783,NULL),(147,41806,19,131935,NULL,331782,NULL),(147,41807,19,131936,NULL,331784,NULL),(147,41808,19,131936,NULL,331783,NULL),(147,41809,19,131936,NULL,331782,NULL),(147,41810,19,131934,NULL,331784,NULL),(147,41811,19,131934,NULL,331783,NULL),(147,41812,19,131934,NULL,331782,NULL);
/*!40000 ALTER TABLE `overlay_tiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `overlays`
--

DROP TABLE IF EXISTS `overlays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overlays` (
  `overlay_id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT NULL,
  `alpha` decimal(3,2) DEFAULT NULL,
  `num_tiles` int(11) DEFAULT NULL,
  `game_overlay_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `icon_media_id` int(11) DEFAULT NULL,
  `sort_index` int(11) DEFAULT NULL,
  `folder_name` varchar(200) DEFAULT NULL,
  `file_uploaded` int(11) DEFAULT NULL,
  PRIMARY KEY (`overlay_id`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `overlays`
--

LOCK TABLES `overlays` WRITE;
/*!40000 ALTER TABLE `overlays` DISABLE KEYS */;
/*!40000 ALTER TABLE `overlays` ENABLE KEYS */;
UNLOCK TABLES;

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
-- Dumping data for table `player_group`
--

LOCK TABLES `player_group` WRITE;
/*!40000 ALTER TABLE `player_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_group` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=14702 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_items`
--

LOCK TABLES `player_items` WRITE;
/*!40000 ALTER TABLE `player_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_items` ENABLE KEYS */;
UNLOCK TABLES;

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
  `event_type` enum('LOGIN','MOVE','PICKUP_ITEM','DROP_ITEM','DROP_NOTE','DESTROY_ITEM','VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_WEBPAGE','VIEW_AUGBUBBLE','VIEW_MAP','VIEW_QUESTS','VIEW_INVENTORY','ENTER_QRCODE','UPLOAD_MEDIA_ITEM','UPLOAD_MEDIA_ITEM_IMAGE','UPLOAD_MEDIA_ITEM_AUDIO','UPLOAD_MEDIA_ITEM_VIDEO','RECEIVE_WEBHOOK','SEND_WEBHOOK','COMPLETE_QUEST','GET_NOTE','GIVE_NOTE_LIKE','GET_NOTE_LIKE','GIVE_NOTE_COMMENT','GET_NOTE_COMMENT') COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_1` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_2` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `event_detail_3` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `player_id` (`player_id`),
  KEY `timestamp` (`timestamp`),
  KEY `event` (`event_type`,`event_detail_1`,`event_detail_2`,`event_detail_3`),
  KEY `game_id` (`game_id`),
  KEY `deleted` (`deleted`),
  KEY `check_for_log` (`player_id`,`game_id`,`event_type`,`event_detail_1`,`deleted`)
) ENGINE=InnoDB AUTO_INCREMENT=2166876 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_log`
--

LOCK TABLES `player_log` WRITE;
/*!40000 ALTER TABLE `player_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_log` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=7092 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `player_state_changes`
--

LOCK TABLES `player_state_changes` WRITE;
/*!40000 ALTER TABLE `player_state_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `player_state_changes` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`player_id`),
  KEY `position` (`latitude`,`longitude`),
  KEY `last_game_id` (`last_game_id`),
  KEY `created` (`created`)
) ENGINE=InnoDB AUTO_INCREMENT=8861 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `players`
--

LOCK TABLES `players` WRITE;
/*!40000 ALTER TABLE `players` DISABLE KEYS */;
/*!40000 ALTER TABLE `players` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=18174 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qrcodes`
--

LOCK TABLES `qrcodes` WRITE;
/*!40000 ALTER TABLE `qrcodes` DISABLE KEYS */;
/*!40000 ALTER TABLE `qrcodes` ENABLE KEYS */;
UNLOCK TABLES;

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
  `exit_to_tab` enum('NONE','GPS','NEARBY','QUESTS','INVENTORY','PLAYER','QR','NOTE','STARTOVER','PICKGAME') COLLATE utf8_unicode_ci DEFAULT NULL,
  `active_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `full_screen_notify` tinyint(1) NOT NULL DEFAULT '1',
  `active_icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  `complete_icon_media_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`quest_id`),
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3192 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quests`
--

LOCK TABLES `quests` WRITE;
/*!40000 ALTER TABLE `quests` DISABLE KEYS */;
/*!40000 ALTER TABLE `quests` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=29398 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirements`
--

LOCK TABLES `requirements` WRITE;
/*!40000 ALTER TABLE `requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `requirements` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`spawnable_id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spawnables`
--

LOCK TABLES `spawnables` WRITE;
/*!40000 ALTER TABLE `spawnables` DISABLE KEYS */;
/*!40000 ALTER TABLE `spawnables` ENABLE KEYS */;
UNLOCK TABLES;

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
  PRIMARY KEY (`web_hook_id`)
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_hooks`
--

LOCK TABLES `web_hooks` WRITE;
/*!40000 ALTER TABLE `web_hooks` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_hooks` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=686 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `web_pages`
--

LOCK TABLES `web_pages` WRITE;
/*!40000 ALTER TABLE `web_pages` DISABLE KEYS */;
/*!40000 ALTER TABLE `web_pages` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2013-01-27 16:41:32
