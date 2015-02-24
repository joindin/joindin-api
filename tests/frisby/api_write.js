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
	frisby.create('Register user')
		.post(baseURL + "/v2.1/users", {
			"username"  : "testUser" + randomSuffix,
			"password"  : "pwpwpwpwpwpw",
			"full_name" : "A test user",
			"email"     : "testuser"+randomSuffix+"@example.com"
		}, {json:true})
		.expectStatus(201)
		.expectHeaderContains("Location", baseURL + "/v2.1/users")
		.after(function(err, res, body) {
      if(res.statusCode == 201) {
        // Call the get user method on the place we're told to go
        testUserByUrl(res.headers.location);
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

module.exports = {
	init      : init,
	testRegisterUser : testRegisterUser
}
