<?php

class TokenController extends ApiController
{
    protected $oauthModel;

    public function handle(Request $request, $db)
    {
        $this->oauthModel = $request->getOauthModel($db);

        // only POST is implemented so far
        if($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        }
        
        return false;
    }

    public function postAction($request, $db)
    {
        // The "password" grant type posts here to exchange a username and
        // password for an access token. This is used by web2.

        $grantType = $request->getParameter('grant_type');
        $username = $request->getParameter('username');
        $password = $request->getParameter('password');

        if ($grantType == 'password') {
            // authenticate the user for web2
            
            $clientId = $request->getParameter('client_id');
            if (!in_array($clientId, $this->config['oauth']['password_client_ids'])) {
                throw new Exception("This client cannot authentiate using the password grant type", 403);
            }

            // generate a temporary access token and then redirect back to the callback
            $username = $request->getParameter('username');
            $password = $request->getParameter('password');
            $result  = $this->oauthModel->createAccessTokenFromPassword(
                $clientId, $username, $password);
    
            if ($result) {
                return array('access_token' => $result['access_token'], 'user_uri' => $result['user_uri']);
            }

        }
        return false;
    }

}
