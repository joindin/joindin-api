// vim: tabstop=2:softtabstop=2:shiftwidth=2 
// ./node_modules/.bin/jasmine-node . 
var apitest  = require('./api');
var datatest = require('./data');

var baseURL;

if (typeof process.env.JOINDIN_API_BASE_URL != 'undefined') {
	baseURL = process.env.JOINDIN_API_BASE_URL;
} else {
	baseURL = "http://api.dev.joind.in:8080";
}

apitest.init(baseURL);

apitest.testIndex(); // Descends into event then talk tests
apitest.testNonexistentEvent();
apitest.testNonexistentTalk();
apitest.testNonexistentEventComment();
apitest.testNonexistentTalkComment();
apitest.testNonexistentUser();
apitest.testExistingUser();

