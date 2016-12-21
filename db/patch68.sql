-- Alter the Charset and Collation of the database to UTF8 to be able
-- to handle Characters outside of ISO-8859-1
-- This resolves https://joindin.jira.com/browse/JOINDIN-725
ALTER DATABASE joindin CHARACTER SET utf8 COLLATE utf8_unicode_ci;

INSERT INTO patch_history SET patch_number = 68;