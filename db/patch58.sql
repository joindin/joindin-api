-- store password reset tokens

CREATE TABLE password_reset_tokens (
      ID int(11) NOT NULL primary key auto_increment,
      user_id int(11) NOT NULL,
      token varchar(255) NOT NULL,
      created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP);

INSERT INTO patch_history SET patch_number = 58;
