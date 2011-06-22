-- phpMyAdmin SQL Dump
-- version 2.11.7.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 02, 2010 at 04:36 PM
-- Server version: 5.0.82
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `aris`
--

-- --------------------------------------------------------

--
-- Table structure for table `editors`
--

CREATE TABLE `editors` (
  `editor_id` int(11) NOT NULL auto_increment,
  `name` varchar(25) NOT NULL,
  `password` varchar(32) NOT NULL,
  `email` varchar(255) NOT NULL,
  `super_admin` enum('0','1') NOT NULL default '0',
  `comments` tinytext NOT NULL,
  PRIMARY KEY  (`editor_id`),
  UNIQUE KEY `unique_name` (`name`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=44 ;

--
-- Dumping data for table `editors`
--

INSERT INTO `editors` VALUES(1, 'editor', '288077f055be4fadc3804a69422dd4f8', '', '1', 'Default Administrator');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL auto_increment,
  `prefix` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `pc_media_id` int(10) unsigned NOT NULL default '0',
  `icon_media_id` int(10) unsigned NOT NULL default '0',
  `media_id` int(10) unsigned NOT NULL default '0',
  `allow_player_created_locations` BOOLEAN NOT NULL DEFAULT '0' ,
  `delete_player_locations_on_reset` BOOLEAN NOT NULL DEFAULT '0' ,
  `is_locational` BOOLEAN NOT NULL DEFAULT '0' ,
  `ready_for_public` BOOLEAN NOT NULL DEFAULT '0' ,
  `on_launch_node_id` INT UNSIGNED NOT NULL DEFAULT '0',
  `game_complete_node_id` INT UNSIGNED NOT NULL DEFAULT  '0',
  PRIMARY KEY  (`game_id`),
  KEY `prefixKey` (  `prefix` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=172 ;

-- --------------------------------------------------------

--
-- Table structure for table `game_editors`
--

CREATE TABLE `game_editors` (
  `game_id` int(11) NOT NULL default '0',
  `editor_id` int(11) NOT NULL default '0',
  UNIQUE KEY `unique` (`game_id`,`editor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `media_id` int(10) unsigned NOT NULL auto_increment,
  `game_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `file_name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `is_icon` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`media_id`,`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=238 ;

--
-- Dumping data for table `media`
--


INSERT INTO `media` VALUES(1, 0, 'Default NPC', 'npc.png', 1);
INSERT INTO `media` VALUES(2, 0, 'Default Item', 'item.png', 1);
INSERT INTO `media` VALUES(3, 0, 'Default Plaque', 'plaque.png', 1);
INSERT INTO `media` VALUES(26, 0, 'Audio CD', 'audiocd.png', 1);
INSERT INTO `media` VALUES(27, 0, 'Video Camera', 'cam.png', 1);
INSERT INTO `media` VALUES(28, 0, 'Dialog', 'dialog.png', 1);
INSERT INTO `media` VALUES(29, 0, 'Disk', 'disk.png', 1);
INSERT INTO `media` VALUES(30, 0, 'Earphone', 'earphone.png', 1);
INSERT INTO `media` VALUES(31, 0, 'Flag', 'flag.png', 1);
INSERT INTO `media` VALUES(32, 0, 'Home', 'home.png', 1);
INSERT INTO `media` VALUES(33, 0, 'Person', 'man.png', 1);
INSERT INTO `media` VALUES(34, 0, 'Microphone', 'mic.png', 1);
INSERT INTO `media` VALUES(35, 0, 'Movie', 'movie.png', 1);
INSERT INTO `media` VALUES(36, 0, 'Camera', 'camera.png', 1);
INSERT INTO `media` VALUES(37, 0, 'Slapper', 'slapper.png', 1);
INSERT INTO `media` VALUES(38, 0, 'TV', 'tv.png', 1);
INSERT INTO `media` VALUES(39, 0, 'Volume', 'volume.png', 1);
INSERT INTO `media` VALUES(40, 0, 'Backpack', 'backpack.png', 0);
INSERT INTO `media` VALUES(41, 0, 'Police Badge', 'badge.png', 0);
INSERT INTO `media` VALUES(42, 0, 'Binocs', 'binocs.png', 0);
INSERT INTO `media` VALUES(43, 0, 'Box - Blue', 'bluebox.png', 0);
INSERT INTO `media` VALUES(44, 0, 'Bonsai Tree', 'bonsia.png', 0);
INSERT INTO `media` VALUES(45, 0, 'Bowl - Gold', 'bowl.png', 0);
INSERT INTO `media` VALUES(46, 0, 'Box', 'box.png', 0);
INSERT INTO `media` VALUES(47, 0, 'Box - Active', 'boxactive.png', 0);
INSERT INTO `media` VALUES(48, 0, 'Boxes on a Shelf', 'boxesonshelf.png', 0);
INSERT INTO `media` VALUES(49, 0, 'Bracelet', 'bracelet.png', 0);
INSERT INTO `media` VALUES(50, 0, 'Briefcase', 'breifcase.png', 0);
INSERT INTO `media` VALUES(51, 0, 'Camera - Nikon', 'camera_nikon.png', 0);
INSERT INTO `media` VALUES(52, 0, 'Camera - Film', 'filmcamera.png', 0);
INSERT INTO `media` VALUES(53, 0, 'Coke Bottle', 'coke.png', 0);
INSERT INTO `media` VALUES(54, 0, 'Compass', 'compass.png', 0);
INSERT INTO `media` VALUES(55, 0, 'Film', 'film.png', 0);
INSERT INTO `media` VALUES(56, 0, 'Film Reel', 'filmreel.png', 0);
INSERT INTO `media` VALUES(57, 0, 'First Aid', 'firstaid.png', 0);
INSERT INTO `media` VALUES(58, 0, 'Box - Fragile', 'fragilebox.png', 0);
INSERT INTO `media` VALUES(59, 0, 'Gameboy', 'gameboy.png', 0);
INSERT INTO `media` VALUES(60, 0, 'Handbag', 'handbag.png', 0);
INSERT INTO `media` VALUES(61, 0, 'iPad', 'ipad.png', 0);
INSERT INTO `media` VALUES(62, 0, 'iPhone', 'iPhone.png', 0);
INSERT INTO `media` VALUES(63, 0, 'Key', 'key.jpg', 0);
INSERT INTO `media` VALUES(64, 0, 'Keys', 'keys.png', 0);
INSERT INTO `media` VALUES(65, 0, 'Lipstick', 'lipstick.png', 0);
INSERT INTO `media` VALUES(66, 0, 'Lollipop', 'lollipop.png', 0);
INSERT INTO `media` VALUES(67, 0, 'Love Letters', 'loveletters.png', 0);
INSERT INTO `media` VALUES(68, 0, 'Mail', 'mail.png', 0);
INSERT INTO `media` VALUES(69, 0, 'Notebook - Moleskine', 'moleskine.png', 0);
INSERT INTO `media` VALUES(70, 0, 'Money', 'money.png', 0);
INSERT INTO `media` VALUES(71, 0, 'Notepad', 'notepad.png', 0);
INSERT INTO `media` VALUES(72, 0, 'Cell Phone - Old', 'oldcellphone.png', 0);
INSERT INTO `media` VALUES(73, 0, 'Coin - Old', 'oldcoin.png', 0);
INSERT INTO `media` VALUES(74, 0, 'Folder - Old', 'oldfolder.png', 0);
INSERT INTO `media` VALUES(75, 0, 'Box - Open', 'openbox.png', 0);
INSERT INTO `media` VALUES(76, 0, 'Parchment', 'parchment.png', 0);
INSERT INTO `media` VALUES(77, 0, 'Pen', 'pen.png', 0);
INSERT INTO `media` VALUES(78, 0, 'Pendant', 'pendant.png', 0);
INSERT INTO `media` VALUES(79, 0, 'Perfume', 'perfume.png', 0);
INSERT INTO `media` VALUES(80, 0, 'Picture', 'picture.png', 0);
INSERT INTO `media` VALUES(81, 0, 'Purse', 'purse.png', 0);
INSERT INTO `media` VALUES(82, 0, 'Wallet', 'pursewallet.png', 0);
INSERT INTO `media` VALUES(83, 0, 'Radio', 'radio.png', 0);
INSERT INTO `media` VALUES(84, 0, 'Radioactive', 'radioactive.png', 0);
INSERT INTO `media` VALUES(85, 0, 'Basket - Red', 'redbasket.png', 0);
INSERT INTO `media` VALUES(86, 0, 'Sports Bag', 'sportsbag.png', 0);
INSERT INTO `media` VALUES(87, 0, 'Suitcase', 'suitcase.png', 0);
INSERT INTO `media` VALUES(88, 0, 'Taqueria', 'taqueria.png', 0);
INSERT INTO `media` VALUES(89, 0, 'Trash - Empty', 'trash.png', 0);
INSERT INTO `media` VALUES(90, 0, 'Trash - Full', 'trashfull.png', 0);
INSERT INTO `media` VALUES(91, 0, 'Umbrella', 'umbrella.png', 0);
INSERT INTO `media` VALUES(92, 0, 'Vase', 'vase.png', 0);
INSERT INTO `media` VALUES(93, 0, 'Vinyl Record', 'vinylrecord.png', 0);


-- --------------------------------------------------------

--
-- Table structure for table `players`
--

CREATE TABLE `players` (
  `player_id` int(11) unsigned NOT NULL auto_increment,
  `first_name` varchar(25) default NULL,
  `last_name` varchar(25) default NULL,
  `email` varchar(50) default NULL,
  `media_id` int(25) unsigned NOT NULL default '0',
  `password` varchar(32) default NULL,
  `user_name` varchar(30) NOT NULL,
  `latitude` double NOT NULL default '0',
  `longitude` double NOT NULL default '0',
  `last_game_id` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`player_id`),
  KEY `position` (`latitude`,`longitude`),
  KEY `last_game_id` (`last_game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=98 ;


-- --------------------------------------------------------

--
-- Table structure for table `player_log`
--

CREATE TABLE `player_log` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `player_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `event_type` enum('LOGIN','MOVE','PICKUP_ITEM','DROP_ITEM','DESTROY_ITEM','VIEW_ITEM','VIEW_NODE','VIEW_NPC','VIEW_MAP','VIEW_QUESTS','VIEW_INVENTORY','ENTER_QRCODE','UPLOAD_MEDIA_ITEM') collate utf8_unicode_ci NOT NULL,
  `event_detail_1` varchar(50) collate utf8_unicode_ci default NULL,
  `event_detail_2` varchar(50) collate utf8_unicode_ci default NULL,
  `deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `player_id` (`player_id`),
  KEY `game_id` (`game_id`),
  KEY `event_type` (`event_type`),
  KEY `event_detail_1` (`event_detail_1`),
  KEY `deleted` (`deleted`),
  KEY `event` (`event_type`,`event_detail_1`,`event_detail_2`),
  KEY `check_for_log` (  `player_id` ,  `game_id` ,  `event_type` ,  `event_detail_1` ,  `deleted` ),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5498 ;

