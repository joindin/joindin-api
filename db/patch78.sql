-- Performance improvements: indexing and simplifiying query

-- TABLE: talk_comments
-- Rationale:
-- * It is common to include the "active=1 and private = 0" filters
-- * (e.g. to count number of comments per talk).
-- rating and user_id are included to allow an index only scan (e.g. in get_talk_rating)
CREATE INDEX idx_talk_priv_act ON talk_comments (talk_id, private, active, rating, user_id);
DROP INDEX idx_talk ON talk_comments;

-- Rationale:
-- Queries using 
-- * user_id = ? AND talk_id = ? and rating > 0
-- * user_id = ? AND talk_id = ? (selecting rating)
CREATE INDEX idx_user_talk_rating ON talk_comments (user_id, talk_id, rating);
DROP INDEX idx_userid ON talk_comments;

-- TABLE: talk_comments
-- Rationale: 
-- * there are many queries having both filters: talk_id and spaker_id
-- * In the function get_talk_rating below, speaker_id is actually selected;

CREATE INDEX idx_talk_speaker ON talk_speaker (talk_id, speaker_id);
DROP INDEX talk_id ON talk_speaker;

-- FUNCTION: get_talk_rating
-- simplify function get_talk_rating (avoid UNION, which should probably be UNION ALL anyway)

-- Drop the function if it exists
DROP FUNCTION IF EXISTS get_talk_rating;


-- Create get_talk_rating function that takes into account ratings that should not be added (speaker ratings, private)
-- + IFNULL() fix for making sure this works on non-claimed talks (jthijssen)
-- + Not using ratings=0, since they didn't rate at all (jthijssen)
DELIMITER //

CREATE FUNCTION get_talk_rating(talk_id INT) RETURNS int
	READS SQL DATA
BEGIN
	DECLARE rating_out INT;
	DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

	SELECT IFNULL(ROUND(AVG(rating)), 0) INTO rating_out
	FROM talk_comments tc
	WHERE
		tc.talk_id = talk_id AND
		tc.rating != 0 AND
		tc.private = 0 AND
		tc.active = 1 AND
		tc.user_id != 0 AND
		tc.user_id NOT IN
		(
			SELECT IFNULL(ts.speaker_id,0) FROM talk_speaker ts WHERE ts.talk_id = talk_id
		);

	RETURN rating_out;
END//


-- Increase patch count
INSERT INTO patch_history SET patch_number = 78;
