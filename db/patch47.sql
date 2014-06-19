# Update source field length on comment tables
# to match the length of the API key "application" column

ALTER TABLE `event_comments` CHANGE `source` `source` VARCHAR(255);
ALTER TABLE `talk_comments` CHANGE `source` `source` VARCHAR(255);

INSERT INTO patch_history SET patch_number = 47;
