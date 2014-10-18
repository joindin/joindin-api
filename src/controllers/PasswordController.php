<?php

class PasswordController extends ApiController
{
    protected $oauthModel;

    /**
     * Handles a request.
     *
     * @param Request $request the requeste
     * @param         $db      the database.
     *
     * @return mixed
     */
    public function handle(Request $request, $db)
    {   
        $this->oauthModel = $request->getOauthModel($db);

        // only POST is implemented so far
        if ($request->getVerb() == 'POST') {
            return $this->postAction($request, $db);
        }

        return false;
    }

    /**
     * Handles a POST request.
     *
     *
     * @param Request $request the request.
     * @param         $db      the database.
     *
     * @return mixed
     */
    public function postAction(Request $request, $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in to change your password", 400);
        }

        $userId    = $request->getUserId();
        $grantType = $request->getParameter('grant_type');	
        
        if (empty($grantType)) {
            throw new Exception('The field "grant_type" is required and must not be empty', 400);
        }

        if ($grantType != 'password') {
            throw new Exception("Grant type not recognised", 400);
        }

        $password = $request->getParameter('password');
	if (empty($password)) {
            throw new Exception('The field "password" is required and must not be empty', 400);
        }

        $clientId     = $request->getParameter('client_id');
        $clientSecret = $request->getParameter('client_secret');
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot authenticate using the password grant type", 403);
        }

        if (!$this->oauthModel->setPasswordForUserId($userId, $password)) {
            throw new Exception("Update of password failed", 403);
        }

        if (isset($this->config['oauth']['expirable_client_ids'])) {
            $this->oauthModel->expireOldTokens($this->config['oauth']['expirable_client_ids']);
        }

        $accessToken = $this->oauthModel->newAccessToken($clientId, $userId);
        if ($accessToken) {
             return array("access_token" => $accessToken);
        }

        throw new Exception("Signin failed", 403);
    }
}

