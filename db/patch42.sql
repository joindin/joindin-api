-- add a unique constraint so that each user can only be attending
-- each event once

alter table user_attend add unique index idx_unique_user_event (uid, eid);

INSERT INTO patch_history SET patch_number = 42;

