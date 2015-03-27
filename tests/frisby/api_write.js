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
        testForgotUsernameWorks(email);
        testResendVerificationFails("doesntexist@lornajane.net");
        testForgotUsernameFails("doesntexist@lornajane.net");
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
        testEditUser(username, password);
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

function testResendVerificationWorks(email) {
  frisby.create('Resend verification')
    .post(baseURL + "/v2.1/emails/verifications", {
      "email": email
    }, {json:true})
    .expectStatus(202)
    .toss();
}

function testResendVerificationFails(email) {
  frisby.create('Resend verification')
    .post(baseURL + "/v2.1/emails/verifications", {
      "email": email
    }, {json:true})
    .expectStatus(400)
    .toss();
}

function testForgotUsernameWorks(email) {
  frisby.create('Forgot username')
    .post(baseURL + "/v2.1/emails/reminders/username", {
      "email": email
    }, {json:true})
    .expectStatus(202)
    .toss();
}

function testForgotUsernameFails(email) {
  frisby.create('Forgot username')
    .post(baseURL + "/v2.1/emails/reminders/username", {
      "email": email
    }, {json:true})
    .expectStatus(400)
    .toss();
}

function testEditUser(username, password) {
  frisby.create('Edit user')
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
        access_token = data.access_token;
        user_uri = data.user_uri;
        frisby.create('Edit user no data')
          .put(user_uri, {
          }, {json:true, headers: {'Authorization' : 'Bearer ' + access_token}})
        .expectStatus(400)
        .toss()
      }
    })
    .toss();
}

module.exports = {
	init      : init,
	testRegisterUser : testRegisterUser,
	testRegisterVerifiedUser : testRegisterVerifiedUser
}
