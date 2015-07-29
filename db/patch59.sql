-- store request tokens we get from twitter

CREATE TABLE twitter_request_tokens (
      ID int(11) NOT NULL primary key auto_increment,
      token varchar(255) NOT NULL,
      secret varchar(255) NOT NULL,
      created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP);

INSERT INTO patch_history SET patch_number = 59;
