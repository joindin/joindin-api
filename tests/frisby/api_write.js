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
}

function testRegisterUser() {
	var randomSuffix = parseInt(Math.random() * 1000000).toString();
  var username = "testUser" + randomSuffix;
  var password = "pwpwpwpwpwpw";
	frisby.create('Register user')
		.post(baseURL + "/v2.1/users", {
			"username"  : username,
			"password"  : password,
			"full_name" : "A test user",
			"email"     : "testuser"+randomSuffix+"@example.com"
		}, {json:true})
		.expectStatus(201)
		.expectHeaderContains("Location", baseURL + "/v2.1/users")
		.after(function(err, res, body) {
      if(res.statusCode == 201) {
        // Call the get user method on the place we're told to go
        testUserByUrl(res.headers.location);
        testUnverifiedUserFailsLogin(username, password);
      }
		})
	.toss();
}

function testRegisterVerifiedUser() {
	var randomSuffix = parseInt(Math.random() * 1000000).toString();
  var username = "testUser" + randomSuffix;
  var password = "pwpwpwpwpwpw";
	frisby.create('Register user')
		.post(baseURL + "/v2.1/users", {
			"username"         : username,
			"password"         : password,
			"full_name"        : "A test user",
			"email"            : "testuser"+randomSuffix+"@example.com",
			"auto_verify_user" : "true"
		}, {json:true})
		.expectStatus(201)
		.expectHeaderContains("Location", baseURL + "/v2.1/users")
		.after(function(err, res, body) {
      if(res.statusCode == 201) {
        // Call the get user method on the place we're told to go
        testUserByUrl(res.headers.location);
        testUserLogin(username, password);
      }
		})
	.toss();
}

function testUserByUrl(url) {
	frisby.create('Get user')
		.get(url)
		.expectStatus(200)
		.expectJSONLength("users", 1)
		.afterJSON(function (users) {
			datatest.checkUserData(users.users[0]);
		})
	.toss();
}

function testUnverifiedUserFailsLogin(username, password) {
  frisby.create('Log in')
    .post(baseURL + "/v2.1/token", {
      "grant_type": "password",
      "username": username,
      "password": password,
      "client_id": "0000",
      "client_secret": "1111"
    }, {json:true})
    .expectStatus(403)
    .toss();

}

function testUserLogin(username, password) {
  frisby.create('Log in')
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
        expect(data.access_token).toBeDefined();
        expect(typeof data.access_token).toBe('string');
        expect(data.user_uri).toBeDefined();
        expect(typeof data.user_uri).toBe('string');
      }
    })
    .toss();
}

module.exports = {
	init      : init,
	testRegisterUser : testRegisterUser,
	testRegisterVerifiedUser : testRegisterVerifiedUser
}
