ALTER TABLE factories ADD trigger_scene_id INT(32) UNSIGNED NOT NULL AFTER trigger_requirement_root_package_id;

ALTER TABLE games ADD notebook_trigger_scene_id INT(32) UNSIGNED NOT NULL AFTER notebook_allow_likes;
ALTER TABLE games ADD notebook_trigger_requirement_root_package_id INT(32) UNSIGNED NOT NULL AFTER notebook_trigger_scene_id;
ALTER TABLE games ADD notebook_trigger_title VARCHAR(255) NOT NULL DEFAULT "" AFTER notebook_trigger_requirement_root_package_id;
ALTER TABLE games ADD notebook_trigger_icon_media_id INT(32) NOT NULL DEFAULT 0 AFTER notebook_trigger_title;
ALTER TABLE games ADD notebook_trigger_distance INT(32) NOT NULL DEFAULT 0 AFTER notebook_trigger_icon_media_id;
ALTER TABLE games ADD notebook_trigger_infinite_distance TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER notebook_trigger_distance;
ALTER TABLE games ADD notebook_trigger_wiggle TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER notebook_trigger_infinite_distance;
ALTER TABLE games ADD notebook_trigger_show_title TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER notebook_trigger_wiggle;
ALTER TABLE games ADD notebook_trigger_hidden TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER notebook_trigger_show_title;
ALTER TABLE games ADD notebook_trigger_on_enter TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER notebook_trigger_hidden;

ALTER TABLE tabs ADD description TEXT NOT NULL AFTER name;
