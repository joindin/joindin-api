-- add a field for the talk stub to live

alter table talks drop column stub;

alter table talks add column stub char(5) UNIQUE;
alter table talks add index (stub, active);

alter table talks add column url_friendly_talk_title varchar(255) after talk_title;

INSERT INTO patch_history SET patch_number = 41;
