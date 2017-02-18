-- Add a new field `can_be_revoked` to the oauth_consumers table
-- That field describes whether tokens from this consumer can be revoked by
-- a user or not. Only tokens from consumers where that flag is set will be shown
-- in the API endpoint /token/
ALTER TABLE `oauth_consumers` ADD COLUMN can_be_revoked tinyint(1) DEFAULT '1';

UPDATE `oauth_consumers` SET can_be_revoked = 0 WHERE consumer_key = 'web2';

INSERT INTO patch_history SET patch_number = 69;
