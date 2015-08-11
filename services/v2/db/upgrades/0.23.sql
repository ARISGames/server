ALTER TABLE dialogs ADD back_button_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER intro_dialog_script_id;
ALTER TABLE plaques ADD back_button_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER event_package_id;
ALTER TABLE web_pages ADD back_button_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER url;
ALTER TABLE games DROP COLUMN tick_script;
ALTER TABLE games DROP COLUMN tick_delay;
