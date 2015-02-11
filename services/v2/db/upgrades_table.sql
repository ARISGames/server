/* must be manually run/created before upgrades can be done */
DROP TABLE IF EXISTS db_upgrades;
CREATE TABLE db_upgrades (
upgrade_id INT(32) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
user_id INT(32) UNSIGNED NOT NULL,
version_major INT(32) UNSIGNED NOT NULL,
version_minor INT(32) UNSIGNED NOT NULL,
timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
