ALTER TABLE talk_comments ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER date_made;
UPDATE talk_comments SET talk_comments.created_at = FROM_UNIXTIME(date_made);

ALTER TABLE talk_comments DROP COLUMN date_made;

INSERT INTO patch_history SET patch_number = 81;
