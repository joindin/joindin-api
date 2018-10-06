ALTER TABLE `blog_cats` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `blog_comments` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `blog_post_cat` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `blog_posts` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `categories` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `countries` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `event_comments` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `event_themes` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `event_track` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `invite_list` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `lang` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `meta_data` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `patch_history` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_cat` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_link_types` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_links` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_speaker` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `talk_track` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `user` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `user_admin` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

ALTER TABLE `user_attend` convert to CHARACTER SET 'utf8mb4' COLLATE utf8mb4_general_ci;

INSERT INTO patch_history SET patch_number = 75;
