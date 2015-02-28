-- create the verified field in the user table; we're going to require all 
-- users verify on registration
ALTER TABLE user 
    ADD verified tinyint(1) not null default 0
    after ID;

-- now set all existing users to be set as verified
UPDATE user set verified = 1;

INSERT INTO patch_history SET patch_number = 51;

