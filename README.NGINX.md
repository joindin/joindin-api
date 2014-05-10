# Running the joind.in-API on nginx

As the joind.in-API currently is optimized for running on an apache-httpd you
will have to do some changes to the code to get everything up and running.

## Get ```apache_request_headers``` up and running.

The joind.in-API requires the ```apache_request_headers```-function. Therefore
you will have to include the following:

    if( !function_exists('apache_request_headers') ) {
        function apache_request_headers() {
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach($_SERVER as $key => $val) {
                if( preg_match($rx_http, $key) ) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    // do some nasty string manipulations to restore the original letter case
                    // this should work in most cases
                    $rx_matches = explode('_', $arh_key);
                    if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh[$arh_key] = $val;
                }
            }
            return( $arh );
        }
    }

The function is simply taken from [php.net/apache_request_headers](http://de2.php.net/manual/en/function.apache-request-headers.php#70810).
Put that into a helper-file and include it in the ```index.php```-file.

## Get a fallback for $_SERVER['PATH_INFO'] up

The nginx-Server seems not to populate the ```$_SERVER['PATH_INFO']```-variable.
So you will have to adapt ```src/inc/Request.php``` around line 41 by adding the
following:

    } else if (isset($_SERVER['REQUEST_URI'])) {
         $this->setPathInfo(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

beneath the ```if (isset($_SERVER['PATH_INFO'])) {```-part.

Then everything should be running like with an apache-httpd.