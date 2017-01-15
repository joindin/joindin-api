#Theres already an unsed table relating to this, drop it
DROP TABLE talk_link_types;
#switch talks to innodb so FKs work
ALTER TABLE `joindin`.`talks` ENGINE=INNODB;

CREATE TABLE `talk_link_types` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `display_name` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=INNODB;


CREATE TABLE `joindin`.`talk_links`(
  `ID` INT NOT NULL AUTO_INCREMENT,
  `talk_id` INT NOT NULL,
  `talk_type` INT NOT NULL,
  `url` TEXT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  FOREIGN KEY (`talk_id`) REFERENCES `joindin`.`talks`(`ID`),
  FOREIGN KEY (`talk_type`) REFERENCES `joindin`.`talk_link_types`(`ID`)
);


INSERT INTO `talk_link_types`(`ID`,`type`,`display_name`) VALUES
(1,'slides_link'),
(2,'video_link'),
(3,'audio_link'),
(4,'code_link'), #github / bitbucket links
(5,'joindin_link'); #Links to another talk on the same topic by the speaker

INSERT INTO patch_history SET patch_number = 67;
