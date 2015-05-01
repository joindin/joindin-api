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

function testAddTalk() {
  var randomSuffix = parseInt(Math.random() * 1000000).toString();
  var username = "testUser" + randomSuffix;
  var password = "pwpwpwpwpwpw";
  var email = "testuser"+randomSuffix+"@example.com";
	frisby.create('Register user')
		.post(baseURL + "/v2.1/users", {
			"username"  : username,
			"password"  : password,
			"full_name" : "A test user",
			"email"     : email
		}, {json:true})
		.expectStatus(201)
		.expectHeaderContains("Location", baseURL + "/v2.1/users")
		.after(function(err, res, body) {
      if(res.statusCode == 201) {
        // Call the get user method on the place we're told to go
        testUserByUrl(res.headers.location);
        testUnverifiedUserFailsLogin(username, password);
        testResendVerificationWorks(email);
        testResendVerificationFails("doesntexist@lornajane.net");
      }
		})
	.toss();
}

function testReadTalk(){}

function testEditTalk() {
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

function testRemoveTalk() {
	frisby.create('Get user')
		.get(url)
		.expectStatus(200)
		.expectJSONLength("users", 1)
		.afterJSON(function (users) {
			datatest.checkUserData(users.users[0]);
		})
	.toss();
}

module.exports = {
	init      : init,
	testRegisterUser : testAddTalk,
	testRegisterVerifiedUser : testRegisterVerifiedUser
}
