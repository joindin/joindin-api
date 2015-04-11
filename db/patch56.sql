-- Enforce uniqueness on usernames
ALTER TABLE user ADD UNIQUE INDEX idx_username (username);
    
-- Increase patch count
INSERT INTO patch_history SET patch_number = 56;
