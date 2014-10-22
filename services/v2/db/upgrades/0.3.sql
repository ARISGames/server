ALTER TABLE scenes ADD description TEXT NOT NULL AFTER name;
ALTER TABLE scenes ADD editor_x INT(32) NOT NULL AFTER description;
ALTER TABLE scenes ADD editor_y INT(32) NOT NULL AFTER editor_x;
