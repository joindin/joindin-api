alter table tags_events add index idx_eventid (event_id);
alter table event_comments add index idx_userid (user_id);
alter table talk_speaker add index idx_speakerid (speaker_id);
alter table pending_talk_claims add index idx_talkid (talk_id);
INSERT INTO patch_history SET patch_number = 54;

