ALTER TABLE triggers CHANGE type type ENUM('IMMEDIATE','LOCATION','QR','TIMER','BEACON','AR') NOT NULL DEFAULT 'IMMEDIATE';
