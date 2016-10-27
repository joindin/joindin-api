-- Replace &#39; with a single quote in all talk-text-fields
UPDATE talks SET
  talk_title = replace(talk_title, '&#39;', "'"),
  talk_desc = replace(talk_title, '&#39;', "'");

-- Replace &#34; with a double quote in all talk-text-fields
UPDATE talks SET
  talk_title = replace(talk_title, '&#34;', '"'),
  talk_desc = replace(talk_title, '&#34;', '"');

INSERT INTO patch_history SET patch_number = 67;