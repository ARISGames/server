ALTER TABLE games ADD field_id_preview int(32) unsigned AFTER theme_id;
ALTER TABLE games ADD field_id_pin     int(32) unsigned AFTER field_id_preview;
ALTER TABLE games ADD field_id_caption int(32) unsigned AFTER field_id_pin;
ALTER TABLE field_options ADD color VARCHAR(255) DEFAULT '' AFTER sort_index;
