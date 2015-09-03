ALTER TABLE plaques ADD continue_function ENUM('NONE', 'EXIT', 'JAVASCRIPT') NOT NULL DEFAULT 'EXIT' AFTER back_button_enabled;
