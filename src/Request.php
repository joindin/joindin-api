<?php

/*
 * Request object
 */

namespace Joindin\Api;

use InvalidArgumentException;
use Joindin\Api\Model\OAuthModel;
use Joindin\Api\View\ApiView;
use Joindin\Api\View\HtmlView;
use Joindin\Api\View\JsonPView;
use Joindin\Api\View\JsonView;
use PDO;
use Teapot\StatusCode\Http;

class Request
{
    /**
     * Output formats
     */
    private const FORMAT_JSON = 'json';
    private const FORMAT_HTML = 'html';

    /**
     * Content-types for the Accepts header
     */
    public const CONTENT_TYPE_JSON = 'application/json';
    public const CONTENT_TYPE_HTML = 'text/html';

    /**
     * HTTP Verbs
     */
    public const HTTP_GET = 'GET';
    public const HTTP_POST = 'POST';
    public const HTTP_PUT = 'PUT';
    public const HTTP_DELETE = 'DELETE';

    /**
     * @var string HTTP verb
     */
    protected $verb;
    public $url_elements;
    public $path_info;
    public $accept = [];
    public $host;
    public $parameters = [];
    public $user_id;
    public $access_token;
    public $version;
    protected $clientIP;
    protected $clientUserAgent;

    protected $oauthModel;
    protected $config;

    /**
     * This Request's View
     */
    protected ?ApiView $view = null;

    /**
     * The priority-ordered list of format choices
     */
    protected array $formatChoices = [self::CONTENT_TYPE_JSON, self::CONTENT_TYPE_HTML];

    /**
     * A list of parameters provided from a Route
     */
    protected array $routeParams = [];

    public string $base = '';

    /** @var int[] */
    public array $paginationParameters = [];

    /**
     * @var string
     */
    public $scheme;

    /**
     * Builds the request object
     *
     * @param array|false $config        The application configuration
     * @param array       $server        The $_SERVER global, injected for testability
     * @param bool        $parseParams   Set to false to skip parsing parameters on
     *                                   construction
     */
    public function __construct($config, array $server, $parseParams = true)
    {
        $this->config = $config;

        if (isset($server['REQUEST_METHOD'])) {
            $this->setVerb($server['REQUEST_METHOD']);
        }

        if (isset($server['PATH_INFO']) && !empty($server['PATH_INFO'])) {
            $this->setPathInfo($server['PATH_INFO']);
        } elseif (isset($server['REQUEST_URI'])) {
            $this->setPathInfo(parse_url($server['REQUEST_URI'], PHP_URL_PATH));
        }

        if (isset($server['HTTP_ACCEPT'])) {
            $this->setAccept($server['HTTP_ACCEPT']);
        }

        if (isset($server['HTTP_HOST'])) {
            $this->setHost($server['HTTP_HOST']);
        }

        if (isset($server['HTTPS']) && ($server['HTTPS'] === 'on')) {
            $this->setScheme('https://');
        } else {
            $this->setScheme('http://');
        }

        $this->setBase($this->getScheme() . $this->getHost());

        if ($parseParams) {
            $this->parseParameters($server);
        }
        $this->setClientInfo();
    }

    /**
     * Sets the IP address and User Agent of the requesting client. It checks for the presence of
     * a Forwarded or X-Forwarded-For header and, if present, it uses the left most address listed.
     * If both of these headers is present, the Forwarded header takes precedence.
     * If the header is not present, it defaults to the REMOTE_ADDR value
     */
    public function setClientInfo()
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        if (array_key_exists('HTTP_FORWARDED', $_SERVER)) {
            $header = new Header('Forwarded', $_SERVER['HTTP_FORWARDED'], ';');
            $header->parseParams();
            $header->setGlue(',');
            $header->parseParams();
            $elementArray = $header->buildEntityArray();
            $elementArray = array_change_key_case($elementArray);

            if (isset($elementArray['for']) && count($elementArray['for'])) {
                $ipAddress = $elementArray['for'][0];
            }

            if (isset($elementArray['user-agent']) && count($elementArray['user-agent'])) {
                $userAgent = $elementArray['user-agent'][0];
            }
        } elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $header = new Header('X-Forwarded-For', $_SERVER['HTTP_X_FORWARDED_FOR'], ',');
            $header->parseParams();
            $elementArray = $header->buildEntityArray();
            $ipAddress    = count($elementArray) ? $elementArray[0][0] : null;
        }
        $this->clientIP        = $ipAddress;
        $this->clientUserAgent = $userAgent;
    }

    /**
     * Gets the priority-ordered list of output format choices
     *
     * @return array
     */
    public function getFormatChoices()
    {
        return $this->formatChoices;
    }

    /**
     * Sets the priority-ordered list of output format choices
     *
     * @param array $formatChoices
     */
    public function setFormatChoices(array $formatChoices)
    {
        $this->formatChoices = $formatChoices;
    }

    /**
     * Gets parameters as determined from the Route
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Sets parameters as determined from the Route
     *
     * @param array $routeParams
     */
    public function setRouteParams(array $routeParams)
    {
        $this->routeParams = $routeParams;
    }

    /**
     * Retrieves the value of a parameter from the request. If a default
     * is provided and the parameter doesn't exist, the default value
     * will be returned instead
     *
     * @param string $param   Parameter to retrieve
     * @param string $default Default to return if parameter doesn't exist
     *
     * @return mixed
     */
    public function getParameter($param, $default = '')
    {
        if (!array_key_exists($param, $this->parameters)) {
            return $default;
        }

        return $this->parameters[$param];
    }

    /**
     * Retrieves a url element by numerical index. If it doesn't exist, and
     * a default is provided, the default value will be returned.
     *
     * @param integer $index Index to retrieve
     * @param string  $default
     *
     * @return string
     */
    public function getUrlElement($index, $default = '')
    {
        $index = (int) $index;

        if (!isset($this->url_elements[$index])) {
            return $default;
        }

        return $this->url_elements[$index];
    }

    /**
     * Determines if the headers indicate that a particular MIME is accepted based
     * on the browser headers
     *
     * @param string $header Mime type to check for
     *
     * @return bool
     */
    public function accepts($header)
    {
        foreach ($this->accept as $accept) {
            if (strpos($accept, $header) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if one of the accept headers matches one of the desired
     * formats and returns that format. If none of the desired formats
     * are found, it will return 'json'
     *
     * @param array|null $formats Formats that we want to serve; set to null to
     *                            use the default list
     *
     * @todo need some real accept header parsing here
     *
     * @return string
     */
    public function preferredContentTypeOutOf(array $formats = null)
    {
        if (!$formats) {
            $formats = $this->getFormatChoices();
        }

        foreach ($formats as $format) {
            if ($this->accepts($format)) {
                return $format;
            }
        }

        return self::FORMAT_JSON;
    }

    /**
     * Gets the View object for this Request, initializing it as appropriate to
     * the accepts header
     *
     * @return ApiView
     */
    public function getView()
    {
        if (!$this->view) {
            $format = $this->getParameter('format', $this->preferredContentTypeOutOf());

            switch ($format) {
                case self::CONTENT_TYPE_HTML:
                case self::FORMAT_HTML:
                    $this->view = new HtmlView();

                    break;
                case self::CONTENT_TYPE_JSON:
                case self::FORMAT_JSON:
                default:
                    // JSONP?
                    $callback = htmlspecialchars($this->getParameter('callback'));

                    if ($callback) {
                        $this->view = new JsonPView($callback);
                    } else {
                        $this->view = new JsonView();
                    }
            }
        }

        return $this->view;
    }

    /**
     * Sets this Request's View object
     *
     * @param ApiView $view
     */
    public function setView(ApiView $view)
    {
        $this->view = $view;
    }

    /**
     * Finds the authorized user from the oauth header and sets it into a
     * variable on the request.
     *
     * @param string $auth_header Authorization header to send into model
     * @param PDO    $db          Database adapter (needed to put into OAuthModel if it's not set already)
     *
     * @throws InvalidArgumentException
     * @return bool
     */
    public function identifyUser($auth_header, PDO $db = null)
    {
        if (
            ($this->getScheme() === 'https://') ||
            (isset($this->config['mode']) && $this->config['mode'] === 'development')
        ) {
            // identify the user
            $oauth_pieces = explode(' ', $auth_header);

            if (count($oauth_pieces) !== 2) {
                throw new InvalidArgumentException('Invalid Authorization Header', Http::BAD_REQUEST);
            }

            // token type must be either 'bearer' or 'oauth'
            if (!in_array(strtolower($oauth_pieces[0]), ['bearer', 'oauth'])) {
                throw new InvalidArgumentException('Unknown Authorization Header Received', Http::BAD_REQUEST);
            }
            $oauth_model = $this->getOauthModel($db);
            $user_id     = $oauth_model->verifyAccessToken($oauth_pieces[1]);
            $this->setUserId($user_id);
            $this->setAccessToken($oauth_pieces[1]);

            return true;
        }

        return false;
    }

    /**
     * What format/method of request is this?  Figure it out and grab the parameters
     *
     * @param array $server The $_SERVER global, injected for testability
     *
     * @return boolean true
     *
     * @todo Make paginationParameters part of this object, add tests for them
     */
    public function parseParameters(array $server)
    {
        // first of all, pull the GET vars
        if (isset($server['QUERY_STRING'])) {
            parse_str($server['QUERY_STRING'], $parameters);
            $this->parameters = $parameters;
            // grab these again, keep them separate for building page hyperlinks
            $this->paginationParameters = $parameters;
        }

        if (!isset($this->paginationParameters['start'])) {
            $this->paginationParameters['start'] = null;
        }

        if (!isset($this->paginationParameters['resultsperpage'])) {
            $this->paginationParameters['resultsperpage'] = 20;
        }

        // now how about PUT/POST bodies? These override what we already had
        if ($this->getVerb() === 'POST' || $this->getVerb() === 'PUT') {
            $body = $this->getRawBody();

            if (
                (isset($server['CONTENT_TYPE']) && $server['CONTENT_TYPE'] === 'application/json')
                || (isset($server['HTTP_CONTENT_TYPE']) && $server['HTTP_CONTENT_TYPE'] === 'application/json')
            ) {
                $body_params = json_decode($body);

                if ($body_params) {
                    foreach ($body_params as $param_name => $param_value) {
                        $this->parameters[$param_name] = $param_value;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns the raw body from POST or PUT calls
     *
     * @return string
     */
    public function getRawBody()
    {
        return file_get_contents('php://input');
    }

    /**
     * Retrieves the verb of the request (method)
     *
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * Allows for manually setting of the request verb
     *
     * @param string $verb Verb to set
     *
     * @return Request
     */
    public function setVerb($verb)
    {
        $this->verb = $verb;

        return $this;
    }

    /**
     * Returns the host from the request
     *
     * @return string|null
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the host on the request
     *
     * @param string $host Host to set
     *
     * @return Request
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Returns the scheme for the request
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Sets the scheme for the request
     *
     * @param string $scheme Scheme to set
     *
     * @return Request
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;

        return $this;
    }

    /**
     * Retrieves or builds an OauthModel object. If it is already built/provided
     * then it can be retrieved without providing a database adapter. If it hasn't
     * been built already, then you must provide a PDO object to put into the
     * model.
     *
     * @param PDO $db [optional] PDO db adapter to put into OAuthModel object
     *
     * @throws InvalidArgumentException
     * @return OAuthModel
     */
    public function getOauthModel(PDO $db = null)
    {
        if ($this->oauthModel === null) {
            if ($db === null) {
                throw new InvalidArgumentException('Db Must be provided to get Oauth Model');
            }
            $this->oauthModel = new OAuthModel($db, $this);
        }

        return $this->oauthModel;
    }

    /**
     * Sets an OAuthModel for the request to use should it need to
     *
     * @param OAuthModel $model Model to set
     *
     * @return Request
     */
    public function setOauthModel(OAuthModel $model)
    {
        $this->oauthModel = $model;

        return $this;
    }

    /**
     * Sets a user id
     *
     * @param string $userId User id to set
     *
     * @return Request
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Retrieves the user id that's been set on the request
     *
     * @return string|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Sets the path info variable. Also explodes the path into url elements
     *
     * @param string $pathInfo Path info to set
     *
     * @return self
     */
    public function setPathInfo($pathInfo)
    {
        $this->path_info    = $pathInfo;
        $this->url_elements = explode('/', $pathInfo);

        return $this;
    }

    /**
     * Retrieves the original path info variable
     *
     * @return string
     */
    public function getPathInfo()
    {
        return $this->path_info;
    }

    /**
     * Sets the accepts variable from the accept header
     *
     * @param string $accepts Accepts header string
     *
     * @return self
     */
    public function setAccept($accepts)
    {
        $this->accept = explode(',', $accepts);

        return $this;
    }

    /**
     * Sets the URI base
     *
     * @param string $base Base to set
     *
     * @return Request
     */
    public function setBase($base)
    {
        $this->base = $base;

        return $this;
    }

    /**
     * Returns the url base
     *
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Sets an access token
     *
     * @param string $token Access token to store
     *
     * @return Request
     */
    public function setAccessToken($token)
    {
        $this->access_token = $token;

        return $this;
    }

    /**
     * Retrieves the access token for this request
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Retrieves the client's IP address
     *
     * @return mixed
     */
    public function getClientIP()
    {
        return $this->clientIP;
    }

    /**
     * Retrieves the client's user agent
     *
     * @return mixed
     */
    public function getClientUserAgent()
    {
        return $this->clientUserAgent;
    }

    /**
     * Fetch a config value by named key.  If the value doesn't exist then
     * return the default value
     *
     * @param string $key     Parameter to retrieve
     * @param string $default Default to return if parameter doesn't exist
     *
     * @return string
     */
    public function getConfigValue($key, $default = '')
    {
        if (!array_key_exists($key, $this->config)) {
            return $default;
        }

        return $this->config[$key];
    }
}
