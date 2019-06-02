<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\TokenMapper;
use PDO;
use Joindin\Api\Request;

class TokenController extends BaseApiController
{
    protected $oauthModel;

    protected $tokenMapper;

    public function postAction(Request $request, PDO $db)
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
            if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
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
                return ['access_token' => $result['access_token'], 'user_uri' => $result['user_uri']];
            }

            throw new Exception("Signin failed", 403);
        }

        throw new Exception("Grant type not recognised", 400);
    }

    public function listTokensForUser(Request $request, PDO $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $mapper = $this->getTokenMapper($db, $request);

        if (!$mapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", 403);
        }

        $tokens = $mapper->getRevokableTokensForUser(
            $request->user_id,
            $this->getResultsPerPage($request),
            $this->getStart($request)
        );

        return $tokens->getOutputView($request, $this->getVerbosity($request));
    }

    public function getToken(Request $request, PDO $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $mapper = $this->getTokenMapper($db, $request);

        if (!$mapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", 403);
        }

        $tokens = $mapper->getTokenByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        return $tokens->getOutputView($request, $this->getVerbosity($request));
    }

    public function revokeToken(Request $request, PDO $db)
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", 401);
        }

        $tokenMapper = $this->getTokenMapper($db, $request);

        if (!$tokenMapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", 403);
        }

        $token = $tokenMapper->getRevokableTokenByIdAndUser(
            $this->getItemId($request),
            $request->user_id
        );

        if (!$token->getTokens()) {
            throw new Exception('No tokens found', 404);
        }

        try {
            $tokenMapper->deleteToken($this->getItemId($request));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 500, $e);
        }

        $request->getView()->setNoRender(true);
        $request->getView()->setResponseCode(204);
        $request->getView()->setHeader(
            'Location',
            $request->base . '/' . $request->version . '/token'
        );
    }

    private function getTokenMapper(PDO $db, Request $request)
    {
        if (!$this->tokenMapper) {
            $this->tokenMapper = new TokenMapper($db, $request);
        }

        return $this->tokenMapper;
    }

    public function setTokenMapper(TokenMapper $tokenMapper)
    {
        $this->tokenMapper = $tokenMapper;
    }
}
