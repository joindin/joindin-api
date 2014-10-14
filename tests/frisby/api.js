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
				}
			}
	}).toss();
}

function testNonexistentEvent() {
	frisby.create('Non-existent event')
		.get(baseURL + '/v2.1/events/100100')
		.expectStatus(404)
		.expectHeader("content-type", "application/json; charset=utf8")
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

function testCreatingEventWithoutLogin(){
	frisby.create('CreatingEvent')
		.post(baseURL + "/v2.1/events/", {
			name : 'testevent',
			description : '',
			location : '',
			start_date : '',
			end_date : '',
			tz_continent : '',
			tz_place : '',
			href : '',
			cfp_url : '',
			cfp_start_date : '',
			cfp_end_date : '',
			tags : []
	})
	.expectStatus(404)
	.expectHeader("content-type", "application/json; charset=utf8");
}

function testEventLifecycle()
{
	username = 'imaadmin';
	password = 'password';
	frisby.create('OAuth2 login')
		//.addHeader('Authorization', "Basic " + new Buffer(username + ":" + password).toString("base64"))
		//.addHeader('Accept', 'application/json')
		.post(baseURL + '/v2.1/token', {
			grant_type: 'password',
			username: username,
			password: password,
			client_id: "web2",
			client_secret: "web2secret"
		},{json: true})
		.expectStatus(200)
		.afterJSON(function(response) {
			frisby.create('CreatingEvent')
				.addHeader('Authorization', 'OAuth ' + response.access_token)
				.post(baseURL + "/v2.1/events/", {
					name : 'testevent',
					description : 'Description',
					location : 'location',
					start_date : '12.02.2014',
					end_date : '13.02.2014',
					tz_continent : 'Europe',
					tz_place : 'Berlin',
					href : '',
					cfp_url : '',
					cfp_start_date : '',
					cfp_end_date : '',
					tags : ['test', 'foo bar']
				}, {json:true})
				.expectStatus(201)
				.expectHeaderContains("Location", baseURL + "/v2.1/events/")
				.after(function(foo, response2){
					frisby.create('Get Newly created Event')
						.addHeader('Authorization', 'OAuth ' + response.access_token)
						.get(response2.headers.location + '?verbose=yes')
						.expectJSON({events:[{
							name : 'testevent',
							description : 'Description',
							location : 'location',
							start_date : '2014-02-12T00:00:00+01:00',
							end_date : '2014-02-13T00:00:00+01:00',
							tz_continent : 'Europe',
							tz_place : 'Berlin',
							tags : ['test', 'foo bar']
						}]})
						.expectStatus(200)
						.afterJSON(function(response3){

							frisby.create('Edit Event')
								.addHeader('Authorization', 'OAuth ' + response.access_token)
								.inspectBody()
								.put(response3.events[0].uri, {
									name : 'testevent2',
									description : 'Description2',
									location : 'location2',
									start_date : '13.02.2014',
									end_date : '14.02.2014',
									tz_continent : 'Asia',
									tz_place : 'Shanghai',
									href : 'http://example.com',
									cfp_url : 'http://example.com/cfp',
									cfp_start_date : '11.02.2014',
									cfp_end_date : '12.02.2014',
									tags : ['php', 'json']
								}, {json:true})
								.expectStatus(201)
								.expectHeaderContains("Location", baseURL + "/v2.1/events/")
								.after(function(foo, response4){
									frisby.create('Get Edited Event')
										.get(response2.headers.location + '?verbose=yes')
										.expectJSON({events:[{
										name : 'testevent2',
										description : 'Description2',
										location : 'location2',
										start_date : '2014-02-13T00:00:00+08:00',
										end_date : '2014-02-14T00:00:00+08:00',
										tz_continent : 'Asia',
										tz_place : 'Shanghai',
										href : 'http://example.com',
										cfp_url : 'http://example.com/cfp',
										cfp_start_date : '2014-02-11T00:00:00+08:00',
										cfp_end_date : '2014-02-12T00:00:00+08:00',
										tags : ['php', 'json']
									}]})

								//.after(function(foo, respponse5){
								//	frisby.create('Edit Event')
								//		.addHeader('Authorization', 'OAuth ' + response.access_token)
								//		.delete(response2.headers.location)
								//		.expectStatus(201)
								//		.inspectBody()
								//		.expectHeaderContains("Location", baseURL + "/v2.1/events/")
								//		.toss()
								//})
										.toss();
									frisby.create('OAuth2 login')
										//.addHeader('Authorization', "Basic " + new Buffer(username + ":" + password).toString("base64"))
										//.addHeader('Accept', 'application/json')
										.post(baseURL + '/v2.1/token', {
											grant_type: 'password',
											username: 'dhart',
											password: 'dhartpass',
											client_id: "web2",
											client_secret: "web2secret"
										},{json: true})
										.expectStatus(200)
										.afterJSON(function(responseFail){
											frisby.create('Edit Event with wrong credentials')
												.addHeader('Authorization', 'OAuth ' + responseFail.access_token)
												.put(response3.events[0].uri, {
													name : 'testevent3',
													description : 'Description3',
													location : 'location2',
													start_date : '13.02.2014',
													end_date : '14.02.2014',
													tz_continent : 'Asia',
													tz_place : 'Shanghai',
													href : 'http://example.com',
													cfp_url : 'http://example.com/cfp',
													cfp_start_date : '11.02.2014',
													cfp_end_date : '12.02.2014',
													tags : ['php', 'json']
												}, {json:true})
												.expectStatus(403)
												.toss();
										})
										.toss();
								})
								.toss();
						})
						.toss();
				})
				.toss();
		})
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
	testNonexistentEvent         : testNonexistentEvent,
	testNonexistentTalk          : testNonexistentTalk,
	testNonexistentEventComment  : testNonexistentEventComment,
	testNonexistentTalkComment   : testNonexistentTalkComment,
	testNonexistentUser          : testNonexistentUser,
	testExistingUser             : testExistingUser,
	testCreatingEventWithoutLogin: testCreatingEventWithoutLogin,
	testEventLifecycle           : testEventLifecycle
}
