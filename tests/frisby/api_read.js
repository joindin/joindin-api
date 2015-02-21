// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');

var baseURL = '';

function init(_baseURL) {
	baseURL = _baseURL;
	frisby.globalSetup({ // globalSetup is for ALL requests
		request: {
			headers: { 'Content-type': 'application/json' }
		}
	});
}

function testIndex() {
	frisby.create('Initial discovery')
		.get(baseURL)
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.expectJSON({
			'events'          : baseURL + '/v2.1/events',
			'hot-events'      : baseURL + '/v2.1/events?filter=hot',
			'upcoming-events' : baseURL + '/v2.1/events?filter=upcoming',
			'past-events'     : baseURL + '/v2.1/events?filter=past',
			'open-cfps'       : baseURL + '/v2.1/events?filter=cfp',
			'docs'            : 'http://joindin.github.io/joindin-api/'
		})

		.afterJSON(function(apis) {

			// Loop over all of the event types
			for (var evType in apis) {

				// Ignore the "docs" link
				if (evType == 'docs') continue;

				frisby.create('Event list for ' + evType)
					.get(apis[evType])
					.expectStatus(200)
					.expectHeader("content-type", "application/json; charset=utf8")
					.afterJSON(function(ev) {
						// Check meta-data
						expect(ev.meta).toContainJsonTypes({"count":Number});
						expect(ev).toContainJsonTypes({"events":Array});

						for(var i in ev.events) {
							datatest.checkEventData(ev.events[i]);
							testEvent(ev.events[i]);
						}

					}).toss();
			}
		})
	.toss();
}

function testEvent(e) {
	// Check for more detail in the events
		frisby.create('Event detail for ' + e.name)
			.get(e.verbose_uri)
			.expectStatus(200)
			.expectHeader("content-type", "application/json; charset=utf8")
			.afterJSON(function(detailedEv) {
				expect(detailedEv.events[0]).toBeDefined();
				expect(typeof detailedEv.events[0]).toBe('object');
				var evt = detailedEv.events[0];
				datatest.checkVerboseEventData(evt);

				testEventComments(evt);
				testEventCommentsVerbose(evt);
				testTalksForEvent(evt);
				testAttendeesForEvent(evt);
				testAttendeesForEventVerbose(evt);
				testTracksForEvent(evt);

			}).toss();

}

function testEventComments(evt) {
	frisby.create('Event comments for ' + evt.name)
		.get(evt.comments_uri + '?resultsperpage=3')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evComments) {
			if(typeof evComments.comments == 'object') {
				for(var i in evComments.comments) {
					var comment = evComments.comments[i];
					datatest.checkEventCommentData(comment);
				}
			}
	}).toss();
}

function testEventCommentsVerbose(evt) {
	frisby.create('Event comments for ' + evt.name + ' (verbose mode)')
		.get(evt.comments_uri + '?resultsperpage=3&verbose=yes')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evComments) {
			if(typeof evComments.comments == 'object') {
				for(var i in evComments.comments) {
					var comment = evComments.comments[i];
					datatest.checkVerboseEventComment(comment);
				}
			}
	}).toss();
}

function testTalksForEvent(evt) {
	frisby.create('Talks at ' + evt.name)
		.get(evt.talks_uri + '?resultsperpage=3')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evTalks) {
			var talk;
			if(typeof evTalks.talks == 'object') {
				for(var i in evTalks.talks) {
					talk = evTalks.talks[i];
					datatest.checkTalkData(talk);
				}

				if(typeof talk == 'object') {
					// check some comments on the last talk
					frisby.create('Comments on talk ' + talk.talk_title)
						.get(talk.comments_uri + '?resultsperpage=3')
						.expectStatus(200)
						.expectHeader("content-type", "application/json; charset=utf8")
						.afterJSON(function(evTalkComments) {
							if(typeof evTalkComments.comments == 'object') {
								for(var i in evTalkComments.comments) {
									var talkComment = evTalkComments.comments[i];
									datatest.checkTalkCommentData(talkComment);
								}
							}
						}).toss();

					// and in verbose mode
					frisby.create('Comments on talk ' + talk.talk_title + ' (verbose mode)')
						.get(talk.comments_uri + '?resultsperpage=3&verbose=yes')
						.expectStatus(200)
						.expectHeader("content-type", "application/json; charset=utf8")
						.afterJSON(function(evTalkComments) {
							if(typeof evTalkComments.comments == 'object') {
								for(var i in evTalkComments.comments) {
									var talkComment = evTalkComments.comments[i];
									datatest.checkVerboseTalkCommentData(talkComment);
								}
							}
						}).toss();
				}
			}
	}).toss();
}

function testAttendeesForEvent(evt) {
	frisby.create('Attendees to ' + evt.name)
			.get(evt.attendees_uri + '?resultsperpage=3')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evUsers) {
			if(typeof evUsers.users == 'object') {
				for(var i in evUsers.users) {
					var user = evUsers.users[i];
					datatest.checkUserData(user);
				}
			}
	}).toss();
}

function testAttendeesForEventVerbose(evt) {
	frisby.create('Attendees to ' + evt.name + ' (verbose format)')
			.get(evt.attendees_uri + '?resultsperpage=3&verbose=yes')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evUsers) {
			if(typeof evUsers.users == 'object') {
				for(var i in evUsers.users) {
					var user = evUsers.users[i];
					datatest.checkVerboseUserData(user);
				}
			}
	}).toss();
}

function testTracksForEvent(evt) {
	frisby.create('Tracks at ' + evt.name)
			.get(evt.tracks_uri + '?resultsperpage=3')
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(evTracks) {
			if(typeof evTracks.tracks == 'object') {
				for(var i in evTracks.tracks) {
					var track = evTracks.tracks[i];
					datatest.checkTrackData(track);
          testTrack(track);
				}
			}

	}).toss();
}

function testSearchEventsByTitle() {
	frisby.create('Event search by title')
		.get(baseURL + "/v2.1/events/?resultsperpage=3&title=php")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(response) {
			// Check meta-data
			expect(response.meta).toContainJsonTypes({"count":Number});
			expect(response).toContainJsonTypes({"events":Array});

			// expect at least some serch results
			expect(response.events.length).toBeGreaterThan(1);

			for(var i in response.events) {
				datatest.checkEventData(response.events[i]);
				testEvent(response.events[i]);
			}
		})
		.toss();
}

function testSearchEventsByNonexistingTitle() {
	frisby.create('Event search by nonexisting title')
		.get(baseURL + "/v2.1/events/?title=some_nonexisting_title")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(response) {
			// Check meta-data
			expect(response.meta).toContainJsonTypes({"count":Number});
			expect(response).toContainJsonTypes({"events":Array});

			// expect no serch results
			expect(response.events.length).toBe(0);
		})
		.toss();
}

function testSearchEventsByTag() {
	frisby.create('Event search by tags')
		.get(baseURL + "/v2.1/events/?resultsperpage=3&tags[]=php")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(response) {
			// Check meta-data
			expect(response.meta).toContainJsonTypes({"count":Number});
			expect(response).toContainJsonTypes({"events":Array});

			for(var i in response.events) {
				datatest.checkEventData(response.events[i]);
				testEvent(response.events[i]);
			}
		})
		.toss();
}

function testNonexistentEvent() {
	frisby.create('Non-existent event')
		.get(baseURL + '/v2.1/events/100100')
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
	.toss();
}

function testTalksIndex() {
	frisby.create('Talks index')
		.get(baseURL + '/v2.1/talks')
		.expectStatus(405)
		.expectHeader("content-type", "application/json; charset=utf8")
		.toss();
}

function testSearchTalksByTitle() {
	frisby.create('Talk search by title')
		.get(baseURL + "/v2.1/talks/?resultsperpage=3&title=php")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(response) {
			// Check meta-data
			expect(response.meta).toContainJsonTypes({"count":Number});
			expect(response).toContainJsonTypes({"talks":Array});

			// expect at least some serch results
			expect(response.talks.length).toBeGreaterThan(1);

			for(var i in response.talks) {
				datatest.checkTalkData(response.talks[i]);
			}
		})
		.toss();
}

function testSearchTalksByNonexistingTitle() {
	frisby.create('Talk search by nonexisting title')
		.get(baseURL + "/v2.1/talks/?resultsperpage=3&title=some_nonexisting_title")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(response) {
			// Check meta-data
			expect(response.meta).toContainJsonTypes({"count":Number});
			expect(response).toContainJsonTypes({"talks":Array});

			// expect no serch results
			expect(response.talks.length).toBe(0);
		})
		.toss();
}

function testNonexistentTalk() {
	frisby.create('Non-existent talk')
		.get(baseURL + '/v2.1/talks/100100100')
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
	.toss();
}

function testNonexistentEventComment() {
	frisby.create('Non-existent event comment')
		.get(baseURL + '/v2.1/event_comments/100100')
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
	.toss();
}

function testNonexistentTalkComment() {
	frisby.create('Non-existent talk comment')
		.get(baseURL + "/v2.1/talk_comments/100100100")
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
		.expectJSON(["Comment not found"])
		.toss();
}

function testNonexistentUser() {
	frisby.create('Non-existent user')
		.get(baseURL + "/v2.1/users/100100100")
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
		.expectJSON(["User not found"])
		.toss();
}

function testExistingUser() {
	frisby.create('Existing user')
		.get(baseURL + "/v2.1/users/1")
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
		.afterJSON(function(allUsers) {
			if (typeof allUsers.users == "object") {
				for (var u in allUsers.users) {
					var user = allUsers.users[u];
					datatest.checkUserData(user);
				}
			}
		})
		.toss();
}

function testTrack(track) {
  frisby.create('Single track')
    .get(track.uri)
		.expectStatus(200)
		.expectHeader("content-type", "application/json; charset=utf8")
    .toss();
}

module.exports = {
	init                         : init,
	testIndex                    : testIndex,
	testEvent                    : testEvent,
	testEventComments            : testEventComments,
	testEventCommentsVerbose     : testEventCommentsVerbose,
	testTalksForEvent            : testTalksForEvent,
	testAttendeesForEvent        : testAttendeesForEvent,
	testAttendeesForEventVerbose : testAttendeesForEventVerbose,
	testTracksForEvent           : testTracksForEvent,
	testSearchEventsByTitle      : testSearchEventsByTitle,
	testSearchEventsByNonexistingTitle : testSearchEventsByNonexistingTitle,
	testSearchEventsByTag        : testSearchEventsByTag,
	testNonexistentEvent         : testNonexistentEvent,
	testTalksIndex               : testTalksIndex,
	testSearchTalksByTitle       : testSearchTalksByTitle,
	testSearchTalksByNonexistingTitle : testSearchTalksByNonexistingTitle,
	testNonexistentTalk          : testNonexistentTalk,
	testNonexistentEventComment  : testNonexistentEventComment,
	testNonexistentTalkComment   : testNonexistentTalkComment,
	testNonexistentUser          : testNonexistentUser,
	testExistingUser             : testExistingUser
}
