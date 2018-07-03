# adding the bio column to the user info

ALTER TABLE `user` add `biography` VARCHAR(400);

INSERT INTO patch_history SET patch_number = 74;
