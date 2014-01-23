--
-- Table structure for table `user_talk_star`
--

DROP TABLE IF EXISTS `user_talk_star`;
CREATE TABLE `user_talk_star` (
  `uid` int(11) NOT NULL,
  `tid` int(11) NOT NULL,
  `ID` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE user_talk_star ADD INDEX idx_talk (tid);

-- Add a unique constraint so that each user can only star each talk once

ALTER TABLE user_talk_star ADD UNIQUE INDEX idx_unique_user_talk (uid, tid);

INSERT INTO patch_history SET patch_number = 43;
