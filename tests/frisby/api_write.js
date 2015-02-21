// vim: tabstop=2:softtabstop=2:shiftwidth=2 

var frisby   = require('frisby');
var datatest = require('./data');

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
	frisby.create('Register user')
		.post(baseURL + "/users", {
			"username"  : "testUser",
			"password"  : "pwpwpwpwpwpw",
			"full_name" : "A test user",
			"email"     : "testuser@example.com"
		})
		.expectStatus(201)
		.expectHeaderContains("Location", baseURL + "/users")

		.afterJSON(function() {
			// TODO: get the user back from the API by following the
			// location header
		})
	.toss();
}

module.exports = {
	init      : init,
	testRegisterUser : testRegisterUser
}
