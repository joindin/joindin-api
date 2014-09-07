-- need more space for storing better-hashed passwords
alter table user modify password varchar(100);

INSERT INTO patch_history SET patch_number = 48;

