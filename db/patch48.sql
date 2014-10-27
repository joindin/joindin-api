-- Add a flag to oauth consumers to indicate whether
-- this key can be used with a grant_type of "password"

ALTER TABLE `oauth_consumers` ADD `enable_password_grant` TINYINT(1) DEFAULT 0;

INSERT INTO patch_history SET patch_number = 48;
