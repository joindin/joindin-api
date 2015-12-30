ALTER TABLE pending_talk_claims ADD user_approved_at DATETIME DEFAULT NULL;
ALTER TABLE pending_talk_claims ADD host_approved_at DATETIME DEFAULT NULL;

INSERT INTO patch_history SET patch_number = 64;
