<?php
/**
 * nginx-helper.php
 * User: adear
 * Date: 9/7/14
 * Time: 3:36 PM
 *
 * In PHP < 5.4, this function is only defined in the apache module. In order to run the site via nginx,
 * we need to define this function and mimic its return value.
 */
function apache_request_headers()
{
    $arh     = array();
    $rx_http = '/\AHTTP_/';
    foreach ($_SERVER as $key => $val) {
        if (preg_match($rx_http, $key)) {
            $arh_key    = preg_replace($rx_http, '', $key);
            $rx_matches = array();
            // do some nasty string manipulations to restore the original letter case
            // this should work in most cases
            $rx_matches = explode('_', $arh_key);
            if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                foreach ($rx_matches as $ak_key => $ak_val) {
                    $rx_matches[$ak_key] = ucfirst($ak_val);
                }
                $arh_key = implode('-', $rx_matches);
            }
            $arh[$arh_key] = $val;
        }
    }

    return ($arh);
}
