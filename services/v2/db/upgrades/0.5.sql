ALTER TABLE games ADD is_siftr tinyint(1) DEFAULT 0 NOT NULL AFTER inventory_weight_cap;
ALTER TABLE games ADD siftr_url varchar(255) DEFAULT NULL AFTER is_siftr;
ALTER TABLE games ADD UNIQUE INDEX (siftr_url);
