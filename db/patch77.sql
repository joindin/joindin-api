# removing NOT NULL constraint preventing oauth access token generation

ALTER TABLE oauth_access_tokens modify column access_token_secret varchar(32);

INSERT INTO patch_history SET patch_number = 77;
