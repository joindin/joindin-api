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

        if ($grantType == 'password') {
            // authenticate the user for web2
            
            $clientId = $request->getParameter('client_id');
            if ($clientId != 'web2') { // this is a bit untidy - Hackathon: put in database ...
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
