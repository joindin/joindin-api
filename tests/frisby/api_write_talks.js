// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');
var util     = require('util');
var url      = require('url');

module.exports = {
  runTalkTests : runTalkTests
}

function runTalkTests(userAccessToken, talks_uri) {
  testCreateTalkFailsIfNotLoggedIn(talks_uri);
  testCreateTalkFailsWithIncorrectData(userAccessToken, talks_uri);
}

function testCreateTalkFailsIfNotLoggedIn(talks_uri)
{
  frisby.create('Create talk fails if not logged in')
    .post(
      talks_uri,
      {},
      {json: true}
    )
    .afterJSON(function(result) {
      expect(result[0]).toContain("You must be logged in");
    })
    .expectStatus(400) // fails as no user token
    .toss();
}

function testCreateTalkFailsWithIncorrectData(access_token, talks_uri) {
  frisby.create('Create talk fails with missing name')
      .post(
      talks_uri,
      {},
      {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
  )
      .expectStatus(400)
      .afterJSON(function (result) {
        expect(result[0]).toContain("The \'talk_title\' field is required");
      })
      .toss();

    frisby.create('Create talk fails with missing description')
        .post(
        talks_uri,
        {'talk_title' : 'talk_title'},
        {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
    )
        .expectStatus(400)
        .afterJSON(function (result) {
            expect(result[0]).toContain("The \'talk_description\' field is required");
        })
        .toss();

    frisby.create('Create talk fails with missing date and time')
        .post(
        talks_uri,
        {
            'talk_title' : 'talk_title',
            'talk_description' : 'talk-description',
        },
        {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
    )
        .expectStatus(400)
        .afterJSON(function (result) {
            expect(result[0]).toContain("The \'start_date\' field is required");
        })
        .toss();

    frisby.create('Create talk fails with missing type')
        .post(
        talks_uri,
        {
            'talk_title' : 'talk_title',
            'talk_description' : 'talk-description',
            'start_date' : (new Date()).toISOString()
        },
        {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
    )
        .expectStatus(400)
        .afterJSON(function (result) {
            expect(result[0]).toContain("The \'type\' field is required");
        })
        .toss();

    frisby.create('Create talk works with minimum fields')
        .post(
            talks_uri,
            {
                'talk_title' : 'talk_title',
                'talk_description' : 'talk-description',
                'start_date' : (new Date()).toISOString(),
                'type' : 'Talk'
            },
            {json : true, headers : {'Authorization' : 'oauth ' + access_token}}
        )
        .expectStatus(201)
        .expectHeaderContains('Location', talks_uri)
        .after(function(err, res, result) {
            talkURI = res.headers.location;
        })
        .toss();
}
