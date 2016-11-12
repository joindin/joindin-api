// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');
var talkstest  = require('./api_write_talks');
var util     = require('util');
var username = '';

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
  username = "testUser" + randomSuffix;
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
    testCreateEventWithEscapedTitle(access_token);
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
    .expectStatus(401) // fails as no user token
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

  frisby.create('Create event fails with start date greater than end')
    .post(
      baseURL + "/v2.1/events",
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-02-01",
        "end_date" : "2015-01-01"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The event start date must be before its end date");
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
    .expectHeaderContains("Location", baseURL + "/v2.1/events")
    .after(function(err, res, body) {
      if(res.statusCode == 201) {
        // We have an event, we can test it!
        var event_uri = res.headers.location;
        testEventByUrl(access_token, event_uri);
        testEditEventFailsIfNotLoggedIn(event_uri);
        testEditEventFailsWithIncorrectData(access_token, event_uri)
        testEditEvent(access_token, res.headers.location);
        testEventComments(access_token, event_uri);
        testEventTracks(access_token, event_uri);
        testAddHostToEvent(access_token, event_uri);
      }
    })
    .toss();
}

function testCreateEventWithEscapedTitle(access_token)
{
    var today = new Date();
    var yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);

    frisby.create('Create Event with Escaped title')
        .post(
            baseURL + "/v2.1/events",
            {
                "name" : "Frisby \"test ' Ölrücklaufstoßdämpfer",
                "description" : "'Ölrücklaufstoßdämpfer\"",
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
        .expectHeaderContains("Location", baseURL + "/v2.1/events")
        .after(function(err, res, body) {
            if(res.statusCode == 201) {
                // We have an event, we can test it!
                var event_uri = res.headers.location;
                frisby.create('Get EVent with EscapedTitle')
                    .get(event_uri)
                    .expectStatus(200) // Created as it is automatically approved
                    .expectJSON("events.?", {
                        "name" : "Frisby \"test ' Ölrücklaufstoßdämpfer",
                        "description" : "'Ölrücklaufstoßdämpfer\""
                    })
                    .toss();
            }
        })
        .toss();
}


function testEventByUrl(access_token, url) {
  frisby.create('Get event from URL')
    .get(url)
    .expectStatus(200)
    .expectJSONLength("events", 1)
    .afterJSON(function (data) {
      datatest.checkEventData(data.events[0]);
      talkstest.runTalkTests(access_token, data.events[0].talks_uri);
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
    .expectStatus(401) // fails as no user token
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

  frisby.create('Edit event fails with start date greater than end')
    .put(
      event_uri,
      {
        "name" : "Frisby test event",
        "description" : "Test description",
        "location" : "here",
        "start_date" : "2015-02-01",
        "end_date" : "2015-01-01"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .afterJSON(function(result) {
      expect(result[0]).toContain("The event start date must be before its end date");
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

function testEventComments(access_token, url) {
  frisby.create('Add event comments')
    .post(
      url + "/comments",
      {
        "comment": "Test event comment to tell you it was awesome",
        "rating": 3
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(201)
    .after(function(err, res, body) {
      if(res.statusCode == 201) {
        var comment_uri = res.headers.location;

        frisby.create("Comment has reported_uri")
          .get(comment_uri)
          .expectStatus(200)
          .afterJSON(function(comment) {
            var report_uri = comment.comments[0].reported_uri;

            frisby.create("Anon user can't report comment")
              .post(report_uri, {}, {json: true})
              .expectStatus(401)
              .expectJSON(["You must log in to report a comment"])
              .toss();

            frisby.create("Logged in user can report comment")
              .post(report_uri, {}, 
                {headers: {'Authorization' : 'Bearer ' + access_token}}
                )
              .expectStatus(202)
              .toss();

          })
          .toss()
      }
    })
    .toss();

  frisby.create('Get reported event comments (event host)')
    .get(url + '/comments/reported',
      {headers: {'Authorization' : 'Bearer ' + access_token}})
    .expectStatus(200)
    .toss();

  frisby.create('Get reported event comments (anon user)')
    .get(url + '/comments/reported',
      {json: true})
    .expectStatus(401)
    .toss();

}



function testEventTracks(access_token, url) {
  var track_name = "Main track";

  frisby.create('Track name required')
    .post(
      url + "/tracks",
      {
        "track_description": "The big room upstairs"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(400)
    .toss();

  frisby.create('Add a track')
    .post(
      url + "/tracks",
      {
        "track_name": track_name,
        "track_description": "The big room upstairs"
      },
      {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
    )
    .expectStatus(201)
    .after(function(err, res, body) {
      if(res.statusCode == 201) {
        var track_uri = res.headers.location;

        frisby.create("Track was created")
          .get(track_uri)
          .expectStatus(200)
          .expectBodyContains(track_name)
          .toss()

        frisby.create("Edit track requires data")
          .put(
              track_uri,
              {},
              {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
          )
          .expectStatus(400)
          .toss()

        frisby.create("Edit track")
          .put(
              track_uri,
              {
                "track_name": "Track 1",
                "track_description": "The big room upstairs"
              },
              {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
          )
          .expectStatus(204)
          .after(function(err, res, body) {
            frisby.create("Track was updated")
              .get(track_uri)
              .expectStatus(200)
              .expectBodyContains("Track 1")
              .after(function(err, res, body) {
                frisby.create("Anon user can't delete track")
                  .delete(
                      track_uri,
                      {},
                      {json: true}
                  )
                  .expectStatus(401)
                  .after(function(err, res, body) {
                    frisby.create("Delete track")
                      .delete(
                          track_uri,
                          {},
                          {json: true, headers: {'Authorization' : 'Bearer ' + access_token}}
                      )
                      .expectStatus(204)
                      .toss()
                  })
                  .toss()
              })
              .toss()
          })
          .toss()
      }
    })
    .toss();
}

function testAddHostToEvent(access_token, url) {

    frisby.create('Unauthenticated Hosts added')
        .post(
            url + "/hosts",
            {},
            {
                json : true,
            }
        )
        .expectStatus(401)
        .toss();

    frisby.create('Username-payload required')
        .post(
            url + "/hosts",
            {},
            {json : true,
                headers : {'Authorization' : 'Bearer ' + access_token}
            }
        )
        .expectStatus(404)
        .toss();

    frisby.create('Username of unknown user provided')
        .post(
            url + "/hosts",
            {
                "host_name" : "claudio"
            },
            {json : true,
                headers : {'Authorization' : 'Bearer ' + access_token}
            }
        )
        .expectStatus(404)
        .toss();
    frisby.create('Add new host')
        .post(
            url + "/hosts",
            {
                "host_name" : "acole"
            },
            {json : true,
                headers : {'Authorization' : 'Bearer ' + access_token}
            }
        )
        .expectStatus(201)
        .after(function(err, res, body) {
            var track_uri = res.headers.location;
            frisby.create('Check Hosts for the event')
                .get(track_uri)
                .expectStatus(200)
                .expectJSON('hosts',[
                    {"host_name" : "A test user for events"},
                    {"host_name" : "Angela Cole", "host_uri" : "http://api.dev.joind.in/v2.1/users/9"}
                ])
                .toss();
        })
        .toss();

}



