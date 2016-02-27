-- table to store the available images for an event
create table event_images (
    id int auto_increment primary key,
    event_id int not null,
    type varchar(30) not null,
    url varchar(255) not null,
    width int,
    height int,
    index event_image_type (event_id, type)
);

-- populate it also with our old-style images
insert into event_images (event_id, type, url, width, height)
select ID, "small", CONCAT("https://joind.in/inc/img/event_icons/", event_icon), 90, 90
from events where (event_icon is not null and event_icon <> "");

INSERT INTO patch_history SET patch_number = 65;
