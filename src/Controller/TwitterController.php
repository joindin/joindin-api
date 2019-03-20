<?php

/**
 * Twitter-specific endpoints live here
 */

namespace Joindin\Api\Controller;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Joindin\Api\Model\TwitterRequestTokenMapper;
use PDO;
use Joindin\Api\Request;

class TwitterController extends BaseApiController
{
    public function getRequestToken(Request $request, PDO $db)
    {
        // only trusted clients can change account details
        $clientId         = $request->getParameter('client_id');
        $clientSecret     = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        // API call to twitter, get request token
        $client = new Client([
            'base_url' => 'https://api.twitter.com/',
            'defaults' => ['auth' => 'oauth']

        ]);

        $oauth = new Oauth1([
            'consumer_key'    => $this->config['twitter']['consumer_key'],
            'consumer_secret' => $this->config['twitter']['consumer_secret'],
        ]);
        $client->getEmitter()->attach($oauth);

        $res = $client->post('oauth/request_token');
        if ($res->getStatusCode() == 200) {
            parse_str($res->getBody(), $data);

            $requestTokenMapper = new TwitterRequestTokenMapper($db);
            // $tokens is instance of TwitterRequestTokenModelCollection
            $tokens      = $requestTokenMapper->create($data['oauth_token'], $data['oauth_token_secret']);
            $output_list = $tokens->getOutputView($request);
            $request->getView()->setHeader('Location', $output_list['twitter_request_tokens'][0]['uri']);
            $request->getView()->setResponseCode(201);

            return $output_list;
        }

        throw new Exception("Twitter: no request token (" . $res->getStatusCode() . ": " . $res->getBody() . ")", 500);
    }

    /**
     * Take the verification token from the client, send to twitter to get an access token,
     * this includes the user's screen name.  From that, look up who they are, create them a
     * new access token and return the same format as we do when logging in a user
     *
     * @param Request $request
     * @param PDO     $db
     *
     * @throws Exception
     * @return array
     */
    public function logUserIn(Request $request, PDO $db)
    {
        $clientId         = $request->getParameter('client_id');
        $clientSecret     = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        // check incoming values
        if (empty($request_token = $request->getParameter("token"))) {
            throw new Exception("The request token must be supplied");
        }
        if (empty($verifier = $request->getParameter("verifier"))) {
            throw new Exception("The verifier code must be supplied");
        }

        // exchange request token for access token
        $client = new Client(
            [
                'base_url' => 'https://api.twitter.com/',
                'defaults' => ['auth' => 'oauth']
            ]
        );

        $oauth = new Oauth1([
            'consumer_key'    => $this->config['twitter']['consumer_key'],
            'consumer_secret' => $this->config['twitter']['consumer_secret'],
            'token'           => $request_token,
        ]);
        $client->getEmitter()->attach($oauth);

        $res = $client->post('oauth/access_token', ['body' => ['oauth_verifier' => $verifier]]);
        if ($res->getStatusCode() == 200) {
            parse_str($res->getBody(), $data);

            // we might want to store oauth_token and oauth_token_secret at some point if we want any
            // more info from twitter
            $twitterUsername = $data['screen_name'];

            $result = $this->oauthModel->createAccessTokenFromTwitterUsername($clientId, $twitterUsername);
            if (!$result) {
                // try to create the user.

                $client1 = new Client(
                    [
                        'base_url' => 'https://api.twitter.com/',
                        'defaults' => ['auth' => 'oauth']
                    ]
                );

                $oauth1 = new Oauth1([
                    'consumer_key'    => $this->config['twitter']['consumer_key'],
                    'consumer_secret' => $this->config['twitter']['consumer_secret'],
                    'token'           => $data['oauth_token'],
                    'token_secret'    => $data['oauth_token_secret'],
                ]);
                $client1->getEmitter()->attach($oauth1);

                try {
                    $res = $client1->get('1.1/account/verify_credentials.json?include_email=true');
                } catch (Exception $e) {
                    throw new Exception('Could not retrieve user-informations from Twitter', 403, $e);
                }

                if ($res->getStatusCode() == 200) {
                    $result = $this->oauthModel->createUserFromTwitterUsername($clientId, $res->json());
                }
            }

            if (!$result) {
                throw new Exception("Could not sign in with Twitter", 403);
            }

            // clean up request token data
            $requestTokenMapper = new TwitterRequestTokenMapper($db);
            $requestTokenMapper->delete($request_token);

            return array('access_token' => $result['access_token'], 'user_uri' => $result['user_uri']);
        }

        throw new Exception("Twitter: error (" . $res->getStatusCode() . ": " . $res->getBody() . ")", 500);
    }
}
