-- Add an index to aid performance

alter table talk_comments add index idx_userid (user_id);

INSERT INTO patch_history SET patch_number = 43;

