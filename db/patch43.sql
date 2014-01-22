--
-- Table structure for table `user_talk_attend`
--

DROP TABLE IF EXISTS `user_talk_attend`;
CREATE TABLE `user_talk_attend` (
  `uid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `ID` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE user_talk_attend ADD INDEX idx_talk (tid);

-- Add a unique constraint so that each user can only be attending each talk once

ALTER TABLE user_talk_attend ADD UNIQUE INDEX idx_unique_user_talk (uid, tid);

INSERT INTO patch_history SET patch_number = 43;
