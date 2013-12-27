-- add url_friendly event name & talk title. Also add a talk stub.

alter table talks add column stub char(5) UNIQUE;
alter table talks add index (stub, active);

alter table talks add column url_friendly_talk_title varchar(255) after talk_title;
alter table talks add unique (url_friendly_talk_title, event_id);

alter table events add column url_friendly_name varchar(255) UNIQUE after event_name;


INSERT INTO patch_history SET patch_number = 41;
