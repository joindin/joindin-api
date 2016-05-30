-- SQL for API write tests
--
-- These aren't safe to run on a live platform, but are very valuable in testing. Before they can be run,
-- this query needs to be run against the database:

insert into oauth_consumers (consumer_key, consumer_secret, user_id, enable_password_grant)
    values ('0000', '1111', '1', '1');
