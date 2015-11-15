-- get rid of the old, unused speaker column

alter table talks drop column speaker;

INSERT INTO patch_history SET patch_number = 62;
