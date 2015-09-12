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
  frisby.create('Register user for testing speakers')
    .post(baseURL + "/v2.1/users", {
      "username"         : username,
      "password"         : password,
      "full_name"        : "A test user for speakers",
      "email"            : "testuser"+randomSuffix+"@example.com",
      "auto_verify_user" : "true"
    }, {json:true})
    .expectStatus(201)
    .after(function(err, res, body) {
      if(res.statusCode == 201) {

        // Secondly, we retreive an access token for our new user
        frisby.create('Log in speakers test user')
          .post(baseURL + "/v2.1/token", {
            "grant_type": "password",
            "username": username,
            "password": password,
            "client_id": "0000",
            "client_secret": "1111",
            }, {json:true})
          .expectStatus(200) // fails if user didn't get autoverified
          .after(function(error, res, data) {
            if(res.statusCode == 200) {
              // Now we run the event tests
              testSpeakers(data.access_token);
            }
          })
          .toss();
      
      }
    })
  .toss();
}

function testSpeakers(access_token) {
    testreadingSpeakersForATalkWorks();
}

function testreadingSpeakersForATalkWorks(){
    frisby.create('Read speakers for a talk')
        .get(baseURL + '/v2.1/talks/200/speakers')
        .expectStatus(200)
        .expectJSON({
          "meta": {
            "count": 3,
            "this_page": "http://api.dev.joind.in/v2.1/talks/200/speakers?start=0&resultsperpage=20",
            "total": 3
          },
          "speakers": [
            {
              "full_name": null,
              "talk_uri": "http://api.dev.joind.in/v2.1/talks/200",
              "twitter_username": null,
              "uri": "http://api.dev.joind.in/v2.1/speakers/299",
              "username": "Christophe Matthews",
              "verbose_uri": "http://api.dev.joind.in/v2.1/speakers/299?verbose=yes"
            },
            {
              "full_name": "Jessica Pierce",
              "talk_uri": "http://api.dev.joind.in/v2.1/talks/200",
              "twitter_username": "",
              "uri": "http://api.dev.joind.in/v2.1/speakers/300",
              "user_uri": "http://api.dev.joind.in/v2.1/users/23",
              "username": "Jessica Pierce",
              "verbose_uri": "http://api.dev.joind.in/v2.1/speakers/300?verbose=yes"
            },
            {
              "full_name": "Donald Fisher",
              "talk_uri": "http://api.dev.joind.in/v2.1/talks/200",
              "twitter_username": "",
              "uri": "http://api.dev.joind.in/v2.1/speakers/301",
              "user_uri": "http://api.dev.joind.in/v2.1/users/16",
              "username": "Donald Fisher",
              "verbose_uri": "http://api.dev.joind.in/v2.1/speakers/301?verbose=yes"
            }
          ]
        })
        .toss();
}


