-- Add the ratings column to the event_comments table
ALTER TABLE event_comments ADD COLUMN `rating` int(11) default NULL;
    
-- Increase patch count
INSERT INTO patch_history SET patch_number = 55;