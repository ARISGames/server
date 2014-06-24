/*
CREATE THE DATABASE
*/
DROP USER 'migration_user'@'127.0.0.1';
CREATE USER 'migration_user'@'127.0.0.1' IDENTIFIED BY 'migration_pass';
DROP DATABASE IF EXISTS migration_db;
CREATE DATABASE migration_db;
GRANT ALL ON migration_db.* TO 'migration_user'@'127.0.0.1';
USE migration_db;

DROP TABLE IF EXISTS user_migrations;
CREATE TABLE user_migrations (
v2_user_id INT(32) UNSIGNED NOT NULL PRIMARY KEY,
v2_read_write_key VARCHAR(255) NOT NULL,
v1_editor_id INT(32) UNSIGNED NOT NULL,
v1_player_id INT(32) UNSIGNED NOT NULL,
v1_read_write_token VARCHAR(255) NOT NULL
);

DROP TABLE IF EXISTS game_migrations;
CREATE TABLE game_migrations (
v2_game_id INT(32) UNSIGNED NOT NULL PRIMARY KEY,
v1_game_id INT(32) UNSIGNED NOT NULL
);

