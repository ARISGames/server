CREATE TABLE `themes` ( `theme_id` int(32) unsigned NOT NULL AUTO_INCREMENT, `name` varchar(255) NOT NULL DEFAULT '', `gmaps_styles` longtext NOT NULL, PRIMARY KEY (`theme_id`) ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

ALTER TABLE games ADD theme_id int(32) unsigned AFTER colors_id;
ALTER TABLE games ADD map_show_labels tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER map_show_players;
ALTER TABLE games ADD map_show_roads tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER map_show_labels;
