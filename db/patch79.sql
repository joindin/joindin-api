ALTER TABLE oauth_access_tokens ADD INDEX idx_user_key (user_id, consumer_key);
ALTER TABLE oauth_access_tokens ADD INDEX idx_consumer_key (consumer_key);
ALTER TABLE oauth_access_tokens ADD INDEX idx_token_user (access_token, user_id);

ALTER TABLE oauth_consumers ADD INDEX idx_user_key (user_id, consumer_key);

INSERT INTO patch_history SET patch_number = 79;
