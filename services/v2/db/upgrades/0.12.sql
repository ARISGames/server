ALTER TABLE instances ADD owner_type ENUM('USER','GAME_CONTENT','GAME') NOT NULL DEFAULT 'GAME_CONTENT' AFTER factory_id;
UPDATE instances SET owner_type = 'GAME_CONTENT' WHERE owner_id = 0;
UPDATE instances SET owner_type = 'USER' WHERE owner_id != 0;
