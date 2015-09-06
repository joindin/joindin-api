<?php

class TokenController extends ApiController
{
    protected $oauthModel;

    public function postAction($request, $db)
    {
        $this->oauthModel = $request->getOauthModel($db);
        // The "password" grant type posts here to exchange a username and
        // password for an access token. This is used by web2.

        $grantType = $request->getParameter('grant_type');
        $username  = $request->getParameter('username');
        $password  = $request->getParameter('password');

        // all fields are required or this makes no sense
        if (empty($grantType)) {
            throw new Exception('The field "grant_type" is required', 400);
        }

        if (empty($username) || empty($password)) {
            throw new Exception('The fields "username" and "password" are both required', 400);
        }

        if ($grantType == 'password') {
            // authenticate the user for web2

            $clientId     = $request->getParameter('client_id');
            $clientSecret = $request->getParameter('client_secret');
            if (! $this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
                throw new Exception("This client cannot authenticate using the password grant type", 403);
            }

            // expire any old tokens
            if (isset($this->config['oauth']['expirable_client_ids'])) {
                $this->oauthModel->expireOldTokens($this->config['oauth']['expirable_client_ids']);
            }

            // generate a temporary access token and then redirect back to the callback
            $username = $request->getParameter('username');
            $password = $request->getParameter('password');
            $result   = $this->oauthModel->createAccessTokenFromPassword(
                $clientId,
                $username,
                $password
            );

            if ($result) {
                return array('access_token' => $result['access_token'], 'user_uri' => $result['user_uri']);
            }

            throw new Exception("Signin failed", 403);
        }

        throw new Exception("Grant type not recognised", 400);
    }
}
