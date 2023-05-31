<?php

namespace Joindin\Api\Controller;

use Exception;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\Model\TokenMapper;
use PDO;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

class TokenController extends BaseApiController
{
    protected OAuthModel $oauthModel;

    protected ?TokenMapper $tokenMapper;

    public function postAction(Request $request, PDO $db): array
    {
        $this->oauthModel = $request->getOauthModel($db);
        // The "password" grant type posts here to exchange a username and
        // password for an access token. This is used by web2.

        $grantType = $request->getParameter('grant_type');
        $username  = $request->getParameter('username');
        $password  = $request->getParameter('password');

        // all fields are required or this makes no sense
        if (empty($grantType)) {
            throw new Exception('The field "grant_type" is required', Http::BAD_REQUEST);
        }

        if (empty($username) || empty($password)) {
            throw new Exception('The fields "username" and "password" are both required', Http::BAD_REQUEST);
        }

        if ($grantType !== 'password') {
            throw new Exception('Grant type not recognised', Http::BAD_REQUEST);
        }

        // authenticate the user for web2

        $clientId     = $request->getStringParameter('client_id');
        $clientSecret = $request->getStringParameter('client_secret');

        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot authenticate using the password grant type", Http::FORBIDDEN);
        }

        // expire any old tokens
        if (isset($this->config['oauth']['expirable_client_ids'])) {
            $this->oauthModel->expireOldTokens($this->config['oauth']['expirable_client_ids']);
        }

        // generate a temporary access token and then redirect back to the callback
        $username = $request->getStringParameter('username');
        $password = $request->getStringParameter('password');
        $result   = $this->oauthModel->createAccessTokenFromPassword(
            $clientId,
            $username,
            $password
        );

        if (!$result) {
            throw new Exception("Signin failed", Http::FORBIDDEN);
        }

        return ['access_token' => $result['access_token'], 'user_uri' => $result['user_uri']];
    }

    public function listTokensForUser(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $mapper = $this->getTokenMapper($db, $request);

        if (!$mapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", Http::FORBIDDEN);
        }

        $tokens = $mapper->getRevokableTokensForUser(
            $request->user_id,
            $this->getResultsPerPage($request),
            $this->getStart($request)
        );

        return $tokens->getOutputView($request, $this->getVerbosity($request));
    }

    public function getToken(Request $request, PDO $db): array
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $mapper = $this->getTokenMapper($db, $request);

        if (!$mapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", Http::FORBIDDEN);
        }

        $tokens = $mapper->getTokenByIdAndUser(
            $this->getItemId($request, 'Token not found'),
            $request->user_id
        );

        return $tokens->getOutputView($request, $this->getVerbosity($request));
    }

    public function revokeToken(Request $request, PDO $db): void
    {
        if (!isset($request->user_id)) {
            throw new Exception("You must be logged in", Http::UNAUTHORIZED);
        }

        $tokenMapper = $this->getTokenMapper($db, $request);

        if (!$tokenMapper->tokenBelongsToTrustedApplication($request->getAccessToken())) {
            throw new Exception("You can not access the token list with this client", Http::FORBIDDEN);
        }

        $token = $tokenMapper->getRevokableTokenByIdAndUser(
            $this->getItemId($request, 'Token not found'),
            $request->user_id
        );

        if (!$token->getTokens()) {
            throw new Exception('No tokens found', Http::NOT_FOUND);
        }

        try {
            $tokenMapper->deleteToken($this->getItemId($request, 'Token not found'));
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), Http::INTERNAL_SERVER_ERROR, $e);
        }

        $request->getView()->setNoRender(true);
        $request->getView()->setResponseCode(Http::NO_CONTENT);
        $request->getView()->setHeader(
            'Location',
            $request->base . '/' . $request->version . '/token'
        );
    }

    private function getTokenMapper(PDO $db, Request $request): TokenMapper
    {
        if (!$this->tokenMapper) {
            $this->tokenMapper = new TokenMapper($db, $request);
        }

        return $this->tokenMapper;
    }

    public function setTokenMapper(TokenMapper $tokenMapper): void
    {
        $this->tokenMapper = $tokenMapper;
    }
}
