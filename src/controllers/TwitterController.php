<?php

/**
 * Twitter-specific endpoints live here
 */

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class TwitterController extends ApiController {
    public function handle(Request $request, $db) {
        // really need to not require this to be declared
    }

    public function getRequestToken($request, $db){
        // only trusted clients can change account details
        $clientId = $request->getParameter('client_id');
        $clientSecret = $request->getParameter('client_secret');
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
        if($res->getStatusCode() == 200) {
            parse_str($res->getBody(), $data);

            $requestTokenMapper = new TwitterRequestTokenMapper($db);
            // token is instance of TwitterRequestTokenModel
            $token = $requestTokenMapper->create($data['oauth_token'], $data['oauth_token_secret']);
            $output_token = $token->transform($request);
            header("Location: " . $output_token['uri'], NULL, 201);
            return $output_token;
        } else {

            error_log("Twitter: no request token (" . $res->getStatusCode() . ": " . $res->getBody() . ")");
            exit;
        }
    }
}
