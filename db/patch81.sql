ALTER TABLE talk_comments DROP COLUMN date_made;

INSERT INTO patch_history SET patch_number = 81;
