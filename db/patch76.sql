/**
 * Issue #649 Switch database tables to InnoDB
 */

USE joindin;

ALTER TABLE `blog_cats` ENGINE=InnoDB;
ALTER TABLE `blog_comments` ENGINE=InnoDB;
ALTER TABLE `blog_post_cat` ENGINE=InnoDB;
ALTER TABLE `blog_posts` ENGINE=InnoDB;
ALTER TABLE `categories` ENGINE=InnoDB;
ALTER TABLE `countries` ENGINE=InnoDB;
ALTER TABLE `email_verification_tokens` ENGINE=InnoDB;
ALTER TABLE `event_comments` ENGINE=InnoDB;
ALTER TABLE `event_themes` ENGINE=InnoDB;
ALTER TABLE `event_track` ENGINE=InnoDB;
ALTER TABLE `events` ENGINE=InnoDB;
ALTER TABLE `invite_list` ENGINE=InnoDB;
ALTER TABLE `lang` ENGINE=InnoDB;
ALTER TABLE `meta_data` ENGINE=InnoDB;
ALTER TABLE `oauth_access_tokens` ENGINE=InnoDB;
ALTER TABLE `oauth_consumers` ENGINE=InnoDB;
ALTER TABLE `patch_history` ENGINE=InnoDB;
ALTER TABLE `pending_talk_claims` ENGINE=InnoDB;
ALTER TABLE `tags` ENGINE=InnoDB;
ALTER TABLE `tags_events` ENGINE=InnoDB;
ALTER TABLE `talk_cat` ENGINE=InnoDB;
ALTER TABLE `talk_comments` ENGINE=InnoDB;
ALTER TABLE `talk_speaker` ENGINE=InnoDB;
ALTER TABLE `talk_track` ENGINE=InnoDB;
ALTER TABLE `user` ENGINE=InnoDB;
ALTER TABLE `user_admin` ENGINE=InnoDB;
ALTER TABLE `user_attend` ENGINE=InnoDB;
ALTER TABLE `user_talk_star` ENGINE=InnoDB;
