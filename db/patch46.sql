-- Add cache count columns for talks and tracks

ALTER TABLE events 
  ADD comment_count int unsigned not null DEFAULT 0, 
  ADD talk_count int unsigned not null DEFAULT 0, 
  ADD track_count int unsigned not null DEFAULT 0;

UPDATE events e SET comment_count = (SELECT COUNT(*) FROM event_comments ec WHERE ec.event_id = e.ID);
UPDATE events e SET talk_count = (SELECT COUNT(*) FROM talks t WHERE t.event_id = e.ID);
UPDATE events e SET track_count = (SELECT COUNT(*) FROM event_track et WHERE et.event_id = e.ID);

INSERT INTO patch_history SET patch_number = 46;

