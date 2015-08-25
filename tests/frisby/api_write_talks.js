// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');
var util     = require('util');
var url      = require('url');

var baseURL = '';

var userAccessToken;

module.exports = {
  init : init,
  setupAndRunTalksTests : setupAndRunTalkTests
}

function init(_baseURL) {
  baseURL = _baseURL;
  frisby.globalSetup({ // globalSetup is for ALL requests
    request: {
      headers: { 'Content-type': 'application/json' }
    }
  });
}


function setupAndRunTalkTests() {
    var eventId;

    frisby.create('GET a random event to add talks to')
        .get(baseURL + "/v2.1/events?verbose=yes")
        .afterJSON(function (body) {
            var rand = Math.round(Math.random() * body.meta.count);
            var uri = url.parse(body.events[rand].uri);
            var path = uri.pathname.split('/');
            eventId = path[path.length - 1];
            user = body.events[rand].hosts[0].host_uri;
            frisby.create('GET host of event ' + eventId)
                .get(user)
                .afterJSON(function(data){
                    username = data.users[0].username;
                    password = username + "pass";
                    frisby.create('Log in user for talk-tests on event ' + eventId)
                        .post(baseURL + "/v2.1/token", {
                            "grant_type": "password",
                            "username": username,
                            "password": password,
                            "client_id": "0000",
                            "client_secret": "1111"
                        }, {json:true})
                        .expectStatus(200) // fails if user didn't get autoverified
                        .after(function(error, res, data) {
                            runTalkTests(data.access_token, eventId);
                        })
                        .toss();
                })
                .toss()
        })
        .toss();
}

function runTalkTests(userAccessToken, eventId) {
  testCreateTalkFailsIfNotLoggedIn(eventId);
  testCreateTalkFailsWithIncorrectData(userAccessToken, eventId);
}

function testCreateTalkFailsIfNotLoggedIn(eventId)
{
  frisby.create('Create talk fails if not logged in')
    .post(
      baseURL + "/v2.1/events/" + eventId + "/talks",
      {},
      {json: true}
    )
    .afterJSON(function(result) {
      expect(result[0]).toContain("You must be logged in");
    })
    .expectStatus(400) // fails as no user token
    .toss();
}

function testCreateTalkFailsWithIncorrectData(access_token, eventId) {
  frisby.create('Create talk fails with missing name')
      .post(
      baseURL + "/v2.1/events/" + eventId + "/talks",
      {},
      {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
  )
      .expectStatus(400)
      .afterJSON(function (result) {
        expect(result[0]).toContain("The talk title field is required");
      })
      .toss();

    frisby.create('Create talk fails with missing description')
        .post(
        baseURL + "/v2.1/events/" + eventId + "/talks",
        {'talk_title' : 'talk_title'},
        {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
    )
        .expectStatus(400)
        .afterJSON(function (result) {
            expect(result[0]).toContain("The talk description field is required");
        })
        .toss();

    frisby.create('Create talk fails with missing date and time')
        .post(
        baseURL + "/v2.1/events/" + eventId + "/talks",
        {
            'talk_title' : 'talk_title',
            'talk_description' : 'talk-description',
        },
        {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
    )
        .expectStatus(400)
        .afterJSON(function (result) {
            expect(result[0]).toContain("Please give the date and time of the talk");
        })
        .toss();

    frisby.create('Create talk works with minimum fields')
        .post(
            baseURL + "/v2.1/events/" + eventId + "/talks",
            {
                'talk_title' : 'talk_title',
                'talk_description' : 'talk-description',
                'start_date' : (new Date()).toISOString()
            },
            {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
        )
        .expectStatus(201)
        .expectHeaderContains('Location', baseURL + "/v2.1/events/" + eventId + "/talks/")
        .after(function(err, res, result) {
            talkURI = res.headers.location;
        })
        .toss();
}