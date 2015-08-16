// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');
var util     = require('util');

var baseURL = '';

function init(_baseURL) {
    baseURL = _baseURL;
    frisby.globalSetup({ // globalSetup is for ALL requests
        request: {
            headers: { 'Content-type': 'application/json' }
        }
    });

    frisby.create('Login User')
        .post(baseURL + "/v2.1/token", {
            "username" : "imaadmin",
            "password" : "password",
            "grant_type" : "password",
            "client_id" : "0000",
            "client_secret" : "1111"
        }, {json: true})
        .expectStatus(200)
        .after(function(err, res, body){
            if (res.statusCode != 200) {
                console.log(res);
                console.log(res.statusCode);
                return;
            }
            testAddTalk(res.body.access_token);
        })
        .toss()
    ;
}

function testAddTalk(token) {

    var data = {
        "talk_title"  : "My cool test-talk",
        "talk_description"  : "Lorem Ipsum",
        "start_date" : "21.12.2015 12:00",
        "duration" : 45,
        "langauge" : "English - UK",
        "type" : "Talk",
        "speakers" : [
            "John Doe",
            "Jane Bar"
        ]
    };
    frisby.create('Create Talk without login fails')
        .post(baseURL + '/v2.1/events/44/talks', {},{json:true})
        .expectStatus(403)
        .addHeader("Authorization", "oauth foo")
        .after(function(err, res, body) {
            if(res.statusCode != 401) {
                return;
            }
        })
        .toss();
    currentData = util._extend({},data);
    currentData.talk_title = '';
    frisby.create("CreateTalk without name")
        .post(baseURL + '/v2.1/events/44/talks', currentData, {json:true})
        .expectStatus(400)
        .addHeader("Authorization", "oauth " + token)
        .toss();
    currentData = util._extend({},data);
    currentData.talk_description = '';
    frisby.create("CreateTalk without name")
        .post(baseURL + '/v2.1/events/44/talks', currentData, {json:true})
        .expectStatus(400)
        .addHeader("Authorization", "oauth " + token)
        .toss();
    currentData = util._extend({},data);
    frisby.create('Create Talk')
        .post(baseURL + "/v2.1/events/44/talks", currentData, {json:true})
        .addHeader("Authorization", "oauth " + token)
        .expectStatus(201)
        .expectHeaderContains("Location", baseURL + "/v2.1/talks")
        .after(function(err, res, body) {
            if(res.statusCode != 201) {
                console.log(body);
                return;
            }
            testReadTalk(token, res.headers.location);
            testEditTalk(token, res.headers.location, data);
        })
        .toss();
}

function testReadTalk(token, uri) {
    frisby.create("read a talk from an URI")
        .get(uri)
        .expectStatus(200)
        .addHeader("authorization", "oauth " + token)
        .toss();
};

function testEditTalk(token, uri, data) {
    editedData = util._extend(data,{
        'talk_title' : 'fooBar'
    });
    frisby.create("Edit a talk from an URI")
        .put(uri,editedData, {json:true})
        .expectStatus(204)
        //.expectHeaderContains("Location", uri)
        .addHeader("Authorization", "oauth " + token)
        .after(function(err, res, body){
            frisby.create('read edited talk')
                .get(uri, data, {json:true})
                .expectStatus(200)
                .expectJSONLength('talks.0.talk_title', 6)
                .toss();
        })
        .toss();
}

module.exports = {
    init      : init,
}