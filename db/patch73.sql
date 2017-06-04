ALTER TABLE `talks` DROP INDEX url_friendly_talk_title;
ALTER TABLE `talks` ADD UNIQUE url_friendly_talk_title(url_friendly_talk_title(150), event_id) ;
ALTER TABLE `talks` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_comments` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `events` DROP INDEX url_friendly_name;
ALTER TABLE `events` ADD UNIQUE url_friendly_name(url_friendly_name(150)) ;
ALTER TABLE `events` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `email_verification_tokens` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `event_images` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `oauth_access_tokens` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `oauth_consumers` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `password_reset_tokens` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `pending_talk_claims` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `reported_event_comments` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `reported_talk_comments` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `tags` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `tags_events` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `twitter_request_tokens` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;
ALTER TABLE `user_talk_star` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

INSERT INTO patch_history SET patch_number = 73;
