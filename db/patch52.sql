-- table to hold the verification tokens we use to check emails
CREATE TABLE email_verification_tokens (
    ID int primary key auto_increment,
    user_id int not null,
    token varchar(255) not null,
    created_date timestamp default CURRENT_TIMESTAMP
);

INSERT INTO patch_history SET patch_number = 52;

