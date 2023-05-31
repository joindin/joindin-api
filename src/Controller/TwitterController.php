<?php

/**
 * Twitter-specific endpoints live here
 */
namespace Joindin\Api\Controller;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Model\TwitterRequestTokenMapper;
use Joindin\Api\Request;
use PDO;
use Teapot\StatusCode\Http;

class TwitterController extends BaseApiController
{
    /**
     * @var OAuthModel
     */
    private OAuthModel $oauthModel;

    public function getRequestToken(Request $request, PDO $db): array
    {
        // only trusted clients can change account details
        $clientId         = $request->getStringParameter('client_id');
        $clientSecret     = $request->getStringParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);

        if (!$this->oauthModel->isClientPermittedPasswordGrant((string) $clientId, (string) $clientSecret)) {
            throw new Exception("This client cannot perform this action", Http::FORBIDDEN);
        }

        $stack = HandlerStack::create();
        $oauth = new Oauth1([
            'consumer_key'    => $this->config['twitter']['consumer_key'],
            'consumer_secret' => $this->config['twitter']['consumer_secret'],
        ]);
        $stack->push($oauth);

        // API call to twitter, get request token
        $client = new Client([
            'base_uri' => 'https://api.twitter.com/',
            'auth' => 'oauth',
            'handler' => $stack
        ]);

        $res = $client->post('oauth/request_token');

        if ($res->getStatusCode() != Http::OK) {
            throw new Exception("Twitter: no request token (" . $res->getStatusCode() . ": " . $res->getBody() . ")", Http::INTERNAL_SERVER_ERROR);
        }

        parse_str($res->getBody(), $data);

        $requestTokenMapper = new TwitterRequestTokenMapper($db, $request);
        // $tokens is instance of TwitterRequestTokenModelCollection
        $tokens      = $requestTokenMapper->create((string) $data['oauth_token'], (string) $data['oauth_token_secret']);
        $output_list = $tokens->getOutputView($request);
        $request->getView()->setHeader('Location', $output_list['twitter_request_tokens'][0]['uri']);
        $request->getView()->setResponseCode(Http::CREATED);

        return $output_list;
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
        $clientId         = $request->getStringParameter('client_id');
        $clientSecret     = $request->getStringParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);

        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", Http::FORBIDDEN);
        }

        // check incoming values
        if (empty($request_token = $request->getParameter("token"))) {
            throw new Exception("The request token must be supplied");
        }

        if (empty($verifier = $request->getParameter("verifier"))) {
            throw new Exception("The verifier code must be supplied");
        }

        // exchange request token for access token
        $stack = HandlerStack::create();
        $oauth = new Oauth1([
            'consumer_key'    => $this->config['twitter']['consumer_key'],
            'consumer_secret' => $this->config['twitter']['consumer_secret'],
            'token'           => $request_token,
        ]);
        $stack->push($oauth);
        $client = new Client([
            'base_uri' => 'https://api.twitter.com/',
            'auth' => 'oauth',
            'handler' => $stack
        ]);

        $res = $client->post('oauth/access_token', ['form_params' => ['oauth_verifier' => $verifier]]);

        if ($res->getStatusCode() != Http::OK) {
            throw new Exception("Twitter: error (" . $res->getStatusCode() . ": " . $res->getBody() . ")", Http::INTERNAL_SERVER_ERROR);
        }

        parse_str($res->getBody(), $data);

        // we might want to store oauth_token and oauth_token_secret at some point if we want any
        // more info from twitter
        $twitterUsername = $data['screen_name'];

        $result = $this->oauthModel->createAccessTokenFromTwitterUsername($clientId, $twitterUsername);

        if (!$result) {
            // try to create the user.
            $stack1 = HandlerStack::create();
            $oauth1 = new Oauth1([
                'consumer_key'    => $this->config['twitter']['consumer_key'],
                'consumer_secret' => $this->config['twitter']['consumer_secret'],
                'token'           => $data['oauth_token'],
                'token_secret'    => $data['oauth_token_secret'],
            ]);
            $stack1->push($oauth1);
            $client1 = new Client([
                'base_uri' => 'https://api.twitter.com/',
                'auth' => 'oauth',
                'handler' => $stack1
            ]);

            try {
                $res = $client1->get('1.1/account/verify_credentials.json?include_email=true');
            } catch (Exception $e) {
                throw new Exception('Could not retrieve user-informations from Twitter', Http::FORBIDDEN, $e);
            }

            if ($res->getStatusCode() === Http::OK) {
                $result = $this->oauthModel->createUserFromTwitterUsername(
                    $clientId,
                    json_decode($res->getBody()->getContents(), null)
                );
            }
        }

        if (!$result) {
            throw new Exception("Could not sign in with Twitter", Http::FORBIDDEN);
        }

        // clean up request token data
        $requestTokenMapper = new TwitterRequestTokenMapper($db, $request);
        $requestTokenMapper->delete($request_token);

        return ['access_token' => $result['access_token'], 'user_uri' => $result['user_uri']];
    }
}
