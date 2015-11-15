-- Add link types table

CREATE TABLE `talk_link_types` (
  `ID`   INT(11)      NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO patch_history
SET patch_number = 60;
