<?php

class FacebookController extends OauthController
{
    protected $accessTokenUrl = 'https://graph.facebook.com/v2.10/oauth/access_token';
    protected $detailsUrl = 'https://graph.facebook.com/me';
    protected $configKey = 'facebook';
    protected $redirectSlug = '/user/facebook-access';

    protected function getUserDetails($accessToken)
    {
        $result = $this->client->get(
            $this->detailsUrl,
            [
                'query' => [
                    'access_token' => $accessToken,
                    'fields' => 'name,email',
                ]
            ]
        );

        if ($result->getStatusCode() != 200) {
            throw new Exception("Could not sign in with Oauth", 403);
        }

        $data = json_decode((string)$result->getBody(), true);

        $record = new OAuthUserModel;
        $record->email = $data['email'];
        $record->fullName = $data['name'];
        $record->userName = $data['id'];

        return $record;
    }

    protected function extractAccessToken($response)
    {
        $response = json_decode($response, true);
        return $response['access_token'];
    }
}
