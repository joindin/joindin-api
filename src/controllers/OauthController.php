<?php
use GuzzleHttp\Client;

abstract class OauthController extends ApiController
{
    private $oauthModel;
    protected $accessTokenUrl = 'https://graph.facebook.com/v2.10/oauth/access_token';
    protected $appId = '';
    protected $appSecret = '';
    protected $redirectUrl = '';
    protected $client;

    protected $detailsUrl = 'https://graph.facebook.com/me';
    protected $detailsParameters = [];

    protected $configKey = '';
    protected $redirectSlug = '';

    abstract protected function getUserDetails($accessToken);
    abstract protected function extractAccessToken($response);

    public function __construct($config = null)
    {
        parent::__construct($config);
        // exchange code for access token
        $this->client = new Client([
            'headers' => ['Accept' => 'application/json']
        ]);

        $invalidConfig =
            empty($this->config[$this->configKey]['app_id']) ||
            empty($this->config[$this->configKey]['app_secret']);

        if ($invalidConfig) {
            throw new Exception('Config not set for ' . static::class, 501);
        }

        $this->appId = $this->config[$this->configKey]['app_id'];
        $this->appSecret = $this->config[$this->configKey]['app_secret'];
        $this->redirectUrl = $this->config['website_url'] . $this->redirectSlug;
    }

    /**
     * Take the verification code from the client, send to Facebook to get an access token.
     * With the access token, read the user's profile to get their email address.
     * From that, look up who they are, create them a new access token and return
     * the same format as we do when logging in a user
     */
    public function logUserIn($request, $db)
    {

        $clientId = $request->getParameter('client_id');
        $clientSecret = $request->getParameter('client_secret');
        $this->oauthModel = $request->getOauthModel($db);
        if (!$this->oauthModel->isClientPermittedPasswordGrant($clientId, $clientSecret)) {
            throw new Exception("This client cannot perform this action", 403);
        }

        // check incoming values
        if (empty($code = $request->getParameter("code"))) {
            throw new Exception("The request code must be supplied");
        }


        $access_token = $this->getAccessToken($code);

        $user = $this->getUserDetails($access_token);

        if (empty($user->email)) {
            throw new Exception("Email address is unavailable", 403);
        }

        $result = $this->oauthModel->createAccessTokenFromTrustedEmail(
            $clientId,
            $user->email,
            $user->fullName,
            $user->userName
        );

        if (!$result) {
            throw new Exception('Issue creating account', 500);
        }

        return [
            'access_token' => $result['access_token'],
            'user_uri' => $result['user_uri'],
        ];
    }

    protected function getAccessToken($code)
    {
        $res = $this->client->get($this->accessTokenUrl, [
            'query' => [
                'client_id' => $this->appId,
                'redirect_uri' => $this->redirectUrl,
                'client_secret' => $this->appSecret,
                'code' => $code,
            ]
        ]);

        if ($res->getStatusCode() != 200) {
            trigger_error('Unexpected Facebook error (' . $res->getStatusCode()
                . ': ' . $res->getBody() . ')', E_USER_WARNING);
            throw new Exception("Unexpected Facebook error", 500);
        }

        return $this->extractAccessToken((string) $res->getBody());
    }
}
