-- add a unique constraint on the event stub

update events set event_stub = null where event_stub = '';
alter table events add unique index idx_unique_stub (event_stub);

INSERT INTO patch_history SET patch_number = 43;

