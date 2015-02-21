// vim: tabstop=2:softtabstop=2:shiftwidth=2 
// ./node_modules/.bin/jasmine-node . 
var apitest  = require('./api_write');
var datatest = require('./data');

var baseURL;

if (typeof process.env.JOINDIN_API_BASE_URL != 'undefined') {
	baseURL = process.env.JOINDIN_API_BASE_URL;
} else {
	baseURL = "http://api.dev.joind.in";
}

apitest.init(baseURL);
apitest.testRegisterUser();

