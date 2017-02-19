INSERT INTO `talk_links` (`talk_id`, `talk_type`, `url`)
SELECT
  id,
  1,
  slides_link
FROM
  talks
WHERE slides_link <> '';

INSERT INTO patch_history SET patch_number = 71;
