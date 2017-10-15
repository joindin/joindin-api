<?php

class GithubController extends OauthController
{
    protected $accessTokenUrl = 'https://github.com/login/oauth/access_token';
    protected $detailsUrl = 'https://api.github.com/user';
    protected $configKey = 'github';
    protected $redirectSlug = '/user/github-access';

    protected function getUserDetails($accessToken)
    {
        $result = $this->client->get(
            $this->detailsUrl,
            [
                'headers' => [
                    'Authorization' => "token $accessToken",
                ]
            ]
        );

        if ($result->getStatusCode() != 200) {
            throw new Exception("Could not sign in with Oauth", 403);
        }

        $data = json_decode((string)$result->getBody(), true);

        $record = new OAuthUserModel;
        $record->email = $data['email'] ?: $data['login'] . '@users.noreply.github.com';
        $record->fullName = $data['name'];
        $record->userName = $data['id'];

        return $record;
    }

    protected function extractAccessToken($response)
    {
        $output = [];
        parse_str($response, $output);
        return $output['access_token'];
    }
}
