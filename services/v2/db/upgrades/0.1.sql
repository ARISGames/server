/*first patch*/
ALTER TABLE games DROP notebook_allow_player_tags;
ALTER TABLE tags DROP player_created;
ALTER TABLE tags ADD curated TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER visible;
ALTER TABLE object_tags CHANGE object_type object_type ENUM('PLAQUE','ITEM','DIALOG','WEB_PAGE','NOTE');
ALTER TABLE notes DROP label_id;
ALTER TABLE notes ADD media_id INT(32) UNSIGNED NOT NULL AFTER description;

CREATE TABLE note_comments ( note_comment_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, game_id INT(32) UNSIGNED NOT NULL, note_id INT(32) UNSIGNED NOT NULL, user_id INT(32) UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL DEFAULT "", description TEXT NOT NULL, created TIMESTAMP DEFAULT '0000-00-00 00:00:00', last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
CREATE INDEX note_comment_note_id ON notes(game_id, note_id);
CREATE TABLE note_likes ( note_like_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, game_id INT(32) UNSIGNED NOT NULL, note_id INT(32) UNSIGNED NOT NULL, user_id INT(32) UNSIGNED NOT NULL, created TIMESTAMP DEFAULT '0000-00-00 00:00:00', last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP);
CREATE INDEX note_like_note_id ON notes(game_id, note_id);

ALTER TABLE triggers ADD infinite_distance TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER distance;
ALTER TABLE factories ADD trigger_infinite_distance TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER trigger_distance;

DROP TABLE note_labels;
DROP TABLE note_media;
