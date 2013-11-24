-- Add a column for API key source

ALTER TABLE `event_comments` ADD consumer_id INT;
ALTER TABLE `talk_comments` ADD consumer_id INT;

-- Bump patch version
INSERT INTO patch_history SET patch_number = 41;
