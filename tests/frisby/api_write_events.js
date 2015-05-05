// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');
var util     = require('util');

var baseURL = '';

module.exports = {
  init : init,
  setupAndRunEventTests : setupAndRunEventTests
}

function init(_baseURL) {
  baseURL = _baseURL;
  frisby.globalSetup({ // globalSetup is for ALL requests
    request: {
      headers: { 'Content-type': 'application/json' }
    }
  });
}


function setupAndRunEventTests() {

  // Firstly we register a user so that we can then log in with this user
  var randomSuffix = parseInt(Math.random() * 1000000).toString();
  var username = "testUser" + randomSuffix;
  var password = "pwpwpwpwpwpw";
  frisby.create('Register user for testing events')
    .post(baseURL + "/v2.1/users", {
      "username"         : username,
      "password"         : password,
      "full_name"        : "A test user for events",
      "email"            : "testuser"+randomSuffix+"@example.com",
      "auto_verify_user" : "true"
    }, {json:true})
    .expectStatus(201)
    .after(function(err, res, body) {
      if(res.statusCode == 201) {

        // Secondly, we retreive an access token for our new user
        frisby.create('Log in events test user')
          .post(baseURL + "/v2.1/token", {
            "grant_type": "password",
            "username": username,
            "password": password,
            "client_id": "0000",
            "client_secret": "1111"
          }, {json:true})
          .expectStatus(200) // fails if user didn't get autoverified
          .after(function(error, res, data) {
            if(res.statusCode == 200) {
              // Now we run the event tests
              testEvents(data.access_token);
            }
          })
          .toss();
      
      }
    })
  .toss();
}

function testEvents(access_token) {
    testCreateEventFailsIfNotLoggedIn();
    testCreateEventFailsWithIncorrectData(access_token);
    testCreatePendingEvent(access_token);
    testCreateApprovedEvent(access_token);
}

function testCreateEventFailsIfNotLoggedIn()
{
  frisby.create('Create event fails if not logged in')
    .post(
      baseURL + "/v2.1/events",
      {},
      {json: true}
    )
    .afterJSON(function(result) {
      expect(result[0]).toContain("You must be logged in");
    })
    .expectStatus(400) // fails as no user token
    .toss();
}

function testCreateEventFailsWithIncorrectData(access_token)
{
  frisby.create('Create event fails with missing name')
    .post(
      baseURL + "/v2.1/events",
      {},
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'name' is a required field");
    })
    .toss();

  frisby.create('Create event fails with missing description')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'description' is a required field");
    })
    .toss();

  frisby.create('Create event fails with missing location')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'location' is a required field");
    })
    .toss();

  frisby.create('Create event fails with missing start_date')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Create event fails with missing end_date')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Create event fails with invalid end_date')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Create event fails with missing tx_continent')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Create event fails with missing tx_place')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Europe",
        "tz_place" : "There"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Create event fails with invalid tx_continent')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Here",
        "tz_place" : "London"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Create event fails with invalid tx_place')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Europe",
        "tz_place" : "Here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();
}

function testCreatePendingEvent(access_token)
{
  var today = new Date();
  var yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  
  frisby.create('Create pending event')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test pending event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : yesterday.toISOString().substring(0, 10),
        "end_date" : today.toISOString().substring(0, 10),
        "tz_continent" : "Europe",
        "tz_place" : "London"
      },
      {json: true, headers: {json: true, 'Authorization' : 'Bearer ' + access_token, 'Content-type': 'application/json'}}
    )
    .expectStatus(202) // Accepted, not created as it is pending approval
    .expectHeaderContains("Location", baseURL + "/v2.1/events")
    .toss();
}

function testCreateApprovedEvent(access_token)
{
  var today = new Date();
  var yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);
  
  frisby.create('Create approved event')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test approved event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : yesterday.toISOString().substring(0, 10),
        "end_date" : today.toISOString().substring(0, 10),
        "tz_continent" : "Europe",
        "tz_place" : "London",
        "auto_approve_event": true
      },
      {json: true, headers: {json: true, 'Authorization' : 'Bearer ' + access_token, 'Content-type': 'application/json'}}
    )
    .expectStatus(201) // Created as it is automatically approved
    .expectHeaderContains("Location", baseURL + "/v2.1/events/")
    .after(function(err, res, body) {
      if(res.statusCode == 201) {
        // We have an event, we can test it!
        var event_uri = res.headers.location;
        testEventByUrl(event_uri);
        testEditEventFailsIfNotLoggedIn(event_uri);
        testEditEventFailsWithIncorrectData(access_token, event_uri)
        testEditEvent(access_token, res.headers.location);
      }
    })
    .toss();
}

function testEventByUrl(url) {
  frisby.create('Get event from URL')
    .get(url)
    .expectStatus(200)
    .expectJSONLength("events", 1)
    .afterJSON(function (data) {
      datatest.checkEventData(data.events[0]);
    })
  .toss();
}

function testEditEventFailsIfNotLoggedIn(event_uri)
{
  var today = new Date();
  var yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);

  frisby.create('Edit event fails if not logged in')
    .post(
      event_uri,
      {
        "name" : "Frisby test approved event (edited)",
        "description" : "Test description (edited)",
        "location" : "here (edited)",
        "start_date" : yesterday.toISOString().substring(0, 10),
        "end_date" : today.toISOString().substring(0, 10),
        "tz_continent" : "Europe",
        "tz_place" : "London",
        "auto_approve_event": true
      },
      {json: true}
    )
    .afterJSON(function(result) {
      expect(result[0]).toContain("You must be logged in");
    })
    .expectStatus(400) // fails as no user token
    .toss();
}

function testEditEventFailsWithIncorrectData(access_token, event_uri)
{
  frisby.create('Edit event fails with missing name')
    .put(
      event_uri,
      {},
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'name' is a required field");
    })
    .toss();

  frisby.create('Edit event fails with missing description')
    .put(
      event_uri,
      {
        "name" : "Frisby test event"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'description' is a required field");
    })
    .toss();

  frisby.create('Edit event fails with missing location')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("'location' is a required field");
    })
    .toss();

  frisby.create('Edit event fails with missing start_date')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Edit event fails with missing end_date')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Edit event fails with invalid end_date')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("Both 'start_date' and 'end_date' must be supplied in a recognised format");
    })
    .toss();

  frisby.create('Edit event fails with missing tx_continent')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Edit event fails with missing tx_place')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Europe",
        "tz_place" : "There"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Edit event fails with invalid tx_continent')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Here",
        "tz_place" : "London"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();

  frisby.create('Edit event fails with invalid tx_place')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-01-01",
        "end_date" : "2015-01-01",
        "tz_continent" : "Europe",
        "tz_place" : "Here"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The fields 'tz_continent' and 'tz_place' must be supplied");
    })
    .toss();
}


function testEditEvent(access_token, event_uri)
{
  var today = new Date();
  var yesterday = new Date();
  yesterday.setDate(yesterday.getDate() - 1);

  frisby.create('Edit users own event')
    .put(
      event_uri,
      {
        "name" : "Frisby test approved event (edited)",
        "description" : "Test description (edited)",
        "location" : "here (edited)",
        "start_date" : yesterday.toISOString().substring(0, 10),
        "end_date" : today.toISOString().substring(0, 10),
        "tz_continent" : "Europe",
        "tz_place" : "London",
        "auto_approve_event": true
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(204)
    .expectHeaderContains("Location", event_uri)
    .toss();
}

