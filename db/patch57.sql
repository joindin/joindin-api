-- Enforce uniqueness on email
ALTER TABLE user ADD UNIQUE INDEX idx_email (email);
    
-- Increase patch count
INSERT INTO patch_history SET patch_number = 57;
