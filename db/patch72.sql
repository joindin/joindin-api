ALTER TABLE `user` ADD COLUMN `trusted` BOOLEAN DEFAULT 0;

INSERT INTO patch_history SET patch_number = 72;
