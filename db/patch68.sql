CREATE TABLE `talk_types_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `talk_id` int(11) NOT NULL,
  `talk_type` int(11) NOT NULL,
  `url` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `talk_id` (`talk_id`),
  KEY `talk_type` (`talk_type`),
  CONSTRAINT `talk_types_link_ibfk_1` FOREIGN KEY (`talk_id`) REFERENCES `talks` (`ID`),
  CONSTRAINT `talk_types_link_ibfk_2` FOREIGN KEY (`talk_type`) REFERENCES `talk_types` (`ID`)
) ENGINE=InnoDB;

CREATE TABLE `talk_types` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB;


INSERT INTO `talk_types`(`ID`,`type`,`display_name`) VALUES
(1,'Slides','slides_link'),
(2,'Video','video_link'),
(3,'Audio','audio_link');

