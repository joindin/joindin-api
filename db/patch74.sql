# adding the bio column to the user info

ALTER TABLE `user` add `biography` TEXT;

INSERT INTO patch_history SET patch_number = 74;
