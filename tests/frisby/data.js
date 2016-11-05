
// Expose the methods outside of this module
module.exports = {
	checkEventData              : checkEventData,
	checkVerboseEventData       : checkVerboseEventData,
	checkEventCommentData       : checkEventCommentData,
	checkVerboseEventComment    : checkVerboseEventComment,
	checkTalkData               : checkTalkData,
	checkUserData               : checkUserData,
    checkHostData               : checkHostData,
	checkVerboseUserData        : checkVerboseUserData,
	checkTrackData              : checkTrackData,
	checkTalkCommentData        : checkTalkCommentData,
	checkVerboseTalkCommentData : checkVerboseTalkCommentData,
    checkLanguageData           : checkLanguageData
}

function checkEventData(ev) {

	if (ev.href != null) {
		expect(ev.href).toBeDefined();
		expect(typeof ev.href).toBe('string');
		if (ev.href != '') {
		//expect(ev.href).toMatch(/^http/);
		}
	}
	if (ev.icon != null) {
		expect(ev.icon).toBeDefined();
		expect(typeof ev.icon).toBe('string');
	}

	// Check required fields
	expect(ev.name).toBeDefined();
	expect(ev.start_date).toBeDefined();
	expect(ev.end_date).toBeDefined();
	expect(ev.tz_continent).toBeDefined();
	expect(ev.tz_place).toBeDefined();
	expect(ev.description).toBeDefined();
	expect(ev.href).toBeDefined();
	expect(ev.icon).toBeDefined();
	expect(ev.attendee_count).toBeDefined();
	expect(ev.uri).toBeDefined();
	expect(ev.verbose_uri).toBeDefined();
        if(typeof ev.average_rating != 'undefined') {
            expect(typeof ev.average_rating).toBe('number');
        }	
	expect(ev.comments_uri).toBeDefined();
	expect(ev.talks_uri).toBeDefined();
	expect(ev.website_uri).toBeDefined();
	expect(ev.attending_uri).toBeDefined();
	expect(typeof ev.name).toBe('string');
	expect(ev.event_comments_count).toBeDefined();
	expect(ev.talks_count).toBeDefined();
	expect(ev.tracks_count).toBeDefined();
	checkDate(ev.start_date);
	checkDate(ev.end_date);
	expect(typeof ev.tz_continent).toBe('string');
	expect(typeof ev.tz_place).toBe('string');
	expect(typeof ev.description).toBe('string');
	expect(typeof ev.attendee_count).toBe('number');
	expect(typeof ev.uri).toBe('string');
	expect(typeof ev.verbose_uri).toBe('string');
	expect(typeof ev.comments_uri).toBe('string');
	expect(typeof ev.talks_uri).toBe('string');
	expect(typeof ev.website_uri).toBe('string');
}

function checkVerboseEventData(evt) {
  expect(evt.name).toBeDefined();
  expect(evt.start_date).toBeDefined();
  expect(evt.end_date).toBeDefined();
  expect(evt.description).toBeDefined();
  expect(evt.href).toBeDefined();
  expect(evt.icon).toBeDefined();
  expect(evt.latitude).toBeDefined();
  expect(evt.longitude).toBeDefined();
  expect(evt.tz_continent).toBeDefined();
  expect(evt.tz_place).toBeDefined();
  expect(evt.location).toBeDefined();
  expect(evt.attendee_count).toBeDefined();
  expect(evt.comments_enabled).toBeDefined();
  if(typeof evt.average_rating != 'undefined') {
    expect(typeof evt.average_rating).toBe('number');
  }  
  expect(evt.event_comments_count).toBeDefined();
  expect(evt.talks_count).toBeDefined();
  expect(evt.tracks_count).toBeDefined();
  expect(evt.cfp_start_date).toBeDefined();
  expect(evt.cfp_end_date).toBeDefined();
  expect(evt.cfp_url).toBeDefined();
  expect(evt.uri).toBeDefined();
  expect(evt.verbose_uri).toBeDefined();
  expect(evt.comments_uri).toBeDefined();
  expect(evt.talks_uri).toBeDefined();
  expect(evt.website_uri).toBeDefined();
  expect(evt.all_talk_comments_uri).toBeDefined();

  expect(typeof evt.name).toBe('string', "Event name");
  checkDate(evt.start_date);
  checkDate(evt.end_date);
  expect(typeof evt.description).toBe('string');
  if (evt.href != null) {
    expect(typeof evt.href).toBe('string', "Event href");
  }
  if (evt.icon != null) {
    expect(typeof evt.icon).toBe('string');
  }
  expect(typeof evt.latitude).toBeTypeOrNull('number');
  expect(typeof evt.longitude).toBeTypeOrNull('number');
  expect(typeof evt.tz_continent).toBe('string');
  expect(typeof evt.tz_place).toBe('string');
  expect(typeof evt.location).toBe('string');
  expect(typeof evt.attendee_count).toBe('number');
  expect(typeof evt.comments_enabled).toBe('number');
  expect(typeof evt.event_comments_count).toBe('number');
  expect(typeof evt.talks_count).toBe('number');
  expect(typeof evt.tracks_count).toBe('number');
  if (evt.cfp_start_date != null) {
    checkDate(evt.cfp_start_date);
  }
  if (evt.cfp_end_date != null) {
    checkDate(evt.cfp_end_date);
  }
  if (evt.cfp_url != null) {
    expect(typeof evt.cfp_url).toBe('string');
  }
  expect(typeof evt.uri).toBe('string');
  expect(typeof evt.verbose_uri).toBe('string');
  expect(typeof evt.comments_uri).toBe('string');
  expect(typeof evt.talks_uri).toBe('string');
  expect(typeof evt.website_uri).toBe('string');
  expect(typeof evt.all_talk_comments_uri).toBe('string');
}

function checkEventCommentData(comment) {
  expect(comment.rating).toBeDefined();
  expect(typeof comment.rating).toBeTypeOrNull('number');    
  expect(comment.comment).toBeDefined();
  expect(typeof comment.comment).toBe('string');
  expect(comment.created_date).toBeDefined();
  checkDate(comment.created_date);
  expect(comment.user_display_name).toBeDefined();
  if(typeof comment.user_uri != 'undefined') {
      expect(typeof comment.user_uri).toBe('string');
  }
  expect(comment.comment_uri).toBeDefined();
  expect(typeof comment.comment_uri).toBe('string');
  expect(comment.verbose_comment_uri).toBeDefined();
  expect(typeof comment.verbose_comment_uri).toBe('string');
  expect(comment.event_uri).toBeDefined();
  expect(typeof comment.event_uri).toBe('string');
  expect(comment.event_comments_uri).toBeDefined();
  expect(typeof comment.event_comments_uri).toBe('string');
}

function checkVerboseEventComment(comment) {
  checkEventCommentData(comment);
  if(typeof comment.source != 'undefined') {
    expect(typeof comment.source).toBeTypeOrNull('string');
  }
  expect(comment.gravatar_hash).toBeDefined();
  expect(typeof comment.gravatar_hash).toBe('string');
}

function checkTalkData(talk) {
  expect(talk.talk_title).toBeDefined();
  expect(typeof talk.talk_title).toBe('string');
  expect(talk.talk_description).toBeDefined();
  expect(typeof talk.talk_description).toBe('string');
  expect(talk.uri).toBeDefined();
  expect(typeof talk.uri).toBe('string');
  expect(talk.verbose_uri).toBeDefined();
  expect(typeof talk.verbose_uri).toBe('string');
  expect(talk.comments_uri).toBeDefined();
  expect(typeof talk.comments_uri).toBe('string');
  expect(talk.event_uri).toBeDefined();
  expect(typeof talk.event_uri).toBe('string');
  expect(talk.website_uri).toBeDefined();
  expect(typeof talk.website_uri).toBe('string');
  expect(talk.verbose_comments_uri).toBeDefined();
  expect(typeof talk.verbose_comments_uri).toBe('string');
  if(typeof talk.slides_link != 'undefined' && talk.slides_link != null) {
    expect(typeof talk.slides_link).toBe('string');
  }
  expect(talk.start_date).toBeDefined();
  checkDate(talk.start_date);
  if(typeof talk.average_rating != 'undefined') {
    expect(typeof talk.average_rating).toBe('number');
  }
  expect(talk.comments_enabled).toBeDefined();
  expect(typeof talk.comments_enabled).toBe('number');
  expect(talk.comment_count).toBeDefined();
  expect(typeof talk.comment_count).toBe('number');
  expect(talk.type).toBeDefined();
  expect(typeof talk.type).toBe('string');
  expect(talk.starred).toBeDefined();
  expect(talk.starred_count).toBeDefined();
  expect(typeof talk.starred_count).toBe('number');
}

function checkUserData(user) {
    expect(user.username).toBeDefined();
    expect(typeof user.username).toBe('string');
    expect(user.full_name).toBeDefined();
    if(typeof user.full_name != 'undefined' && user.full_name != null) {
      expect(typeof user.full_name).toBe('string');
    }
    expect(user.twitter_username).toBeDefined();
    if(typeof user.twitter_username != 'undefined' && user.twitter_username != null) {
      expect(typeof user.twitter_username).toBe('string');
    }
    expect(user.uri).toBeDefined();
    expect(typeof user.uri).toBe('string');
    expect(user.verbose_uri).toBeDefined();
    expect(typeof user.verbose_uri).toBe('string');
    expect(user.website_uri).toBeDefined();
    expect(typeof user.website_uri).toBe('string');
    expect(user.talks_uri).toBeDefined();
    expect(typeof user.talks_uri).toBe('string');
    expect(user.attended_events_uri).toBeDefined();
    expect(typeof user.attended_events_uri).toBe('string');
    expect(user.hosted_events_uri).toBeDefined();
    expect(typeof user.hosted_events_uri).toBe('string');
}

function checkHostData(user) {
    expect(user.user_name).toBeDefined();
    expect(typeof user.user_name).toBe('string');
    expect(user.user_uri).toBeDefined();
    expect(typeof user.user_uri).toBe(string)
}

function checkVerboseUserData(user) {
    expect(user.username).toBeDefined();
    expect(typeof user.username).toBe('string');
    expect(user.full_name).toBeDefined();
    if(typeof user.full_name != 'undefined' && user.full_name != null) {
      expect(typeof user.full_name).toBe('string');
    }
    expect(user.twitter_username).toBeDefined();
    if(typeof user.twitter_username != 'undefined' && user.twitter_username != null) {
      expect(typeof user.twitter_username).toBe('string');
    }
    expect(user.gravatar_hash).toBeDefined();
    expect(typeof user.gravatar_hash).toBe('string');
    expect(user.uri).toBeDefined();
    expect(typeof user.uri).toBe('string');
    expect(user.verbose_uri).toBeDefined();
    expect(typeof user.verbose_uri).toBe('string');
    expect(user.website_uri).toBeDefined();
    expect(typeof user.website_uri).toBe('string');
    expect(user.talks_uri).toBeDefined();
    expect(typeof user.talks_uri).toBe('string');
    expect(user.attended_events_uri).toBeDefined();
    expect(typeof user.attended_events_uri).toBe('string');
    expect(user.hosted_events_uri).toBeDefined();
    expect(typeof user.hosted_events_uri).toBe('string');
}

function checkTrackData(track) {
    expect(track.track_name).toBeDefined();
    expect(typeof track.track_name).toBe('string');
    expect(track.track_description).toBeDefined();
    expect(typeof track.track_description).toBe('string');
    expect(track.talks_count).toBeDefined();
    expect(typeof track.talks_count).toBe('number');
    expect(track.uri).toBeDefined();
    expect(typeof track.uri).toBe('string');
    expect(track.verbose_uri).toBeDefined();
    expect(typeof track.verbose_uri).toBe('string');
    expect(track.event_uri).toBeDefined();
    expect(typeof track.event_uri).toBe('string');
}

function checkTalkCommentData(comment) {
  expect(comment.rating).toBeDefined();
  expect(typeof comment.rating).toBe('number');
  expect(comment.comment).toBeDefined();
  expect(typeof comment.comment).toBe('string');
  expect(comment.created_date).toBeDefined();
  checkDate(comment.created_date);
  expect(comment.user_display_name).toBeDefined();
  if(typeof comment.user_uri != 'undefined') {
      expect(typeof comment.user_uri).toBe('string');
  }
  expect(comment.talk_title).toBeDefined();
  expect(typeof comment.talk_title).toBe('string');
  expect(comment.uri).toBeDefined();
  expect(typeof comment.uri).toBe('string');
  expect(comment.verbose_uri).toBeDefined();
  expect(typeof comment.verbose_uri).toBe('string');
  expect(comment.talk_uri).toBeDefined();
  expect(typeof comment.talk_uri).toBe('string');
  expect(comment.talk_comments_uri).toBeDefined();
  expect(typeof comment.talk_comments_uri).toBe('string');
}

function checkVerboseTalkCommentData(comment) {
  checkTalkCommentData(comment);
  if(typeof comment.source != 'undefined') {
    expect(typeof comment.source).toBeTypeOrNull('string');
  }
  expect(comment.gravatar_hash).toBeDefined();
  expect(typeof comment.gravatar_hash).toBe('string');
}

function checkLanguageData(language) {
    expect(language.name).toBeDefined();
    expect(typeof language.name).toBe('string');
    expect(language.code).toBeDefined();
    expect(typeof language.code).toBe('string');
    expect(language.uri).toBeDefined();
    expect(typeof language.uri).toBe('string');
    expect(language.verbose_uri).toBeDefined();
    expect(typeof language.verbose_uri).toBe('string');
}

function checkDate(fieldValue) {
  dateVal = new Date(fieldValue);
  expect(getObjectClass(dateVal)).toBe('Date');
  return true;
}

/**
 * getObjectClass 
 * 
 * stolen from: http://blog.magnetiq.com/post/514962277/finding-out-class-names-of-javascript-objects
 */
function getObjectClass(obj) {
  if (obj && obj.constructor && obj.constructor.toString) {
    var arr = obj.constructor.toString().match(
      /function\s*(\w+)/);

    if (arr && arr.length == 2) {
      return arr[1];
    }
  }

  return undefined;
}

