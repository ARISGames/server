ALTER TABLE games ADD tick_script TEXT DEFAULT "" AFTER type;
ALTER TABLE games ADD tick_delay INT(32) NOT NULL DEFAULT 0 AFTER tick_script;
