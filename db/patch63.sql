-- create storage for reported comments

create table reported_talk_comments(
    ID int auto_increment primary key,
    talk_comment_id int not null, 
    reporting_user_id int not null,
    reporting_date datetime not null defaut CURRENT_TIMESTAMP,
    decision varchar(20),
    deciding_user_id int,
    deciding_date datetime
);

create table reported_event_comments(
    ID int auto_increment primary key,
    event_comment_id int not null, 
    reporting_user_id int not null,
    reporting_date datetime not null defaut CURRENT_TIMESTAMP,
    decision varchar(20),
    deciding_user_id int,
    deciding_date datetime
);


INSERT INTO patch_history SET patch_number = 63;
