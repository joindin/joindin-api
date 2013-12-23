-- add a field for the talk stub to live

alter table talks drop column stub;

alter table talks add column stub char(5) UNIQUE;

alter table talks add index (stub, active);

INSERT INTO patch_history SET patch_number = 41;
