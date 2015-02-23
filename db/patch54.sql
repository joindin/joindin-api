-- Drop the function if it exists
DROP FUNCTION IF EXISTS get_event_rating;


-- Create get_event_rating function that takes into account ratings that should not be added by event admins
DELIMITER //

CREATE FUNCTION get_event_rating(event_id INT) RETURNS int
	READS SQL DATA
BEGIN
	DECLARE rating_out INT;
	DECLARE EXIT HANDLER FOR NOT FOUND RETURN NULL;

	SELECT IFNULL(ROUND(AVG(rating)), 0) INTO rating_out
	FROM event_comments ec
	WHERE
		ec.event_id = event_id AND
		ec.rating != 0 AND
		ec.user_id NOT IN
		(
			SELECT IFNULL(ua.uid,0) FROM user_admin ua WHERE ua.rid = event_id AND ua.rtype='event'
			UNION
			SELECT 0
		);

	RETURN rating_out;
END//


-- Increase patch count
INSERT INTO patch_history SET patch_number = 54;
