<?php

/**
 * Facebook-specific endpoints live here
 */

use GuzzleHttp\Client;

class FacebookController extends ApiController
{
    /**
     * Take the verification code from the client, send to Facebook to get an access token.
     * With the access token, read the user's profile to get their email address.
     * From that, look up who they are, create them a new access token and return
     * the same format as we do when logging in a user
     *
     * @param Request $request
     * @param PDO $db
     *
     * @throws Exception
     * @return array
     */
    public function logUserIn(Request $request, PDO $db)
    {
        if (empty($this->config['facebook']['app_id'])
            || empty($this->config['facebook']['app_secret'])) {
            throw new Exception("Cannot login via Facebook", 501);
        }

        $clientId = $request->getParameter('client_id');
        $clientSecret = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        // check incoming values
        if (empty($code = $request->getParameter("code"))) {
            throw new Exception("The request code must be supplied");
        }

        // exchange code for access token
        $client = new Client([
            'headers' => ['Accept' => 'application/json']
        ]);

        $res = $client->get('https://graph.facebook.com/v2.3/oauth/access_token', [
            'query' => [
                'client_id' => $this->config['facebook']['app_id'],
                'redirect_uri' => $this->config['website_url'] . '/user/facebook-access',
                'client_secret' => $this->config['facebook']['app_secret'],
                'code' => $code,
            ]
        ]);

        if ($res->getStatusCode() == 200) {
            $data = json_decode((string)$res->getBody(), true);
            $access_token = $data['access_token'];

            // retrieve email address from Facebook profile
            $res = $client->get('https://graph.facebook.com/me', [
                'query' => [
                    'access_token' => $access_token,
                ]
            ]);
            if ($res->getStatusCode() == 200) {
                $data = json_decode((string)$res->getBody(), true);
                if (!array_key_exists('email', $data)) {
                    throw new Exception("Email address is unavailable", 403);
                }
                $email = $data['email'];
                
                $result = $this->oauthModel->createAccessTokenFromTrustedEmail($clientId, $email);
                if ($result) {
                    return array('access_token' => $result['access_token'], 'user_uri' => $result['user_uri']);
                }
            }

            throw new Exception("Could not sign in with Facebook", 403);
        }

        trigger_error('Unexpected Facebook error (' . $res->getStatusCode()
            . ': ' . $res->getBody() . ')', E_USER_WARNING);
        throw new Exception("Unexpected Facebook error", 500);
    }
}
