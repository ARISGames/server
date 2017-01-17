ALTER TABLE triggers ADD ar_target_img_scale_x DOUBLE NOT NULL DEFAULT 1.0 AFTER ar_target_id;
ALTER TABLE triggers ADD ar_target_img_scale_y DOUBLE NOT NULL DEFAULT 1.0 AFTER ar_target_img_scale_x;
