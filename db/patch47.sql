ALTER TABLE talk_comments CHANGE rating content_rating int(11) DEFAULT NULL;
ALTER TABLE talk_comments ADD speaker_rating int(11) DEFAULT NULL;

DROP FUNCTION IF EXISTS get_talk_rating;
DROP FUNCTION IF EXISTS get_talk_content_rating;
DROP FUNCTION IF EXISTS get_talk_speaker_rating;
DROP FUNCTION IF EXISTS get_comment_rating;

DELIMITER //

CREATE FUNCTION get_talk_rating(talk_id INT) RETURNS int
READS SQL DATA
  BEGIN
    DECLARE rating_out INT;
    DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

    SELECT IFNULL(ROUND(AVG((content_rating + COALESCE(speaker_rating, 2.5))/2)), 0) INTO rating_out
    FROM talk_comments tc
    WHERE
      tc.talk_id = talk_id AND
      tc.content_rating != 0 AND
      tc.user_id NOT IN
      (
        SELECT IFNULL(ts.speaker_id,0) FROM talk_speaker ts WHERE ts.talk_id = talk_id
        UNION
        SELECT 0
      );

    RETURN rating_out;
  END;
//

CREATE FUNCTION get_talk_content_rating(talk_id INT) RETURNS int
READS SQL DATA
  BEGIN
    DECLARE rating_out INT;
    DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

    SELECT IFNULL(ROUND(AVG(content_rating)), 0) INTO rating_out
    FROM talk_comments tc
    WHERE
      tc.talk_id = talk_id AND
      tc.content_rating != 0 AND
      tc.user_id NOT IN
      (
        SELECT IFNULL(ts.speaker_id,0) FROM talk_speaker ts WHERE ts.talk_id = talk_id
        UNION
        SELECT 0
      );

    RETURN rating_out;
  END;
//

CREATE FUNCTION get_talk_speaker_rating(talk_id INT) RETURNS int
READS SQL DATA
  BEGIN
    DECLARE rating_out INT;
    DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

    SELECT ROUND(AVG(speaker_rating)) INTO rating_out
    FROM talk_comments tc
    WHERE
      tc.talk_id = talk_id AND
      tc.speaker_rating != 0 AND
      tc.user_id NOT IN
      (
        SELECT IFNULL(ts.speaker_id,0) FROM talk_speaker ts WHERE ts.talk_id = talk_id
        UNION
        SELECT 0
      );

    RETURN rating_out;
  END;
//

CREATE FUNCTION get_comment_rating(talk_id INT, comment_id INT) RETURNS int
READS SQL DATA
  BEGIN
    DECLARE rating_out INT;
    DECLARE EXIT HANDLER FOR NOT FOUND RETURN 0;

    SELECT COALESCE(ROUND((content_rating + COALESCE(speaker_rating, content_rating))/2), 0) INTO rating_out
    FROM talk_comments tc
    WHERE
      tc.id = comment_id AND
      tc.content_rating != 0 AND
      tc.user_id NOT IN
      (
        SELECT COALESCE(ts.speaker_id, -1) FROM talk_speaker ts WHERE ts.talk_id = talk_id
      );

    RETURN rating_out;
  END;
//
DELIMITER ;

# SELECT get_talk_rating(13), get_talk_rating(4), get_talk_rating(96), get_talk_rating(186), get_talk_rating(146), get_talk_rating(180), get_talk_rating(114), get_talk_rating(26), get_talk_rating(125), get_talk_rating(91), get_talk_rating(22), get_talk_rating(150);
# SELECT get_comment_rating(1), get_comment_rating(44), get_comment_rating(179);

INSERT INTO patch_history SET patch_number = 47;