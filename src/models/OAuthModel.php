<?php

class OAuthModel {

    protected $_db;
    protected $base;
    protected $version;

    /**
     * Object constructor, sets up the db and some objects need request too
     *
     * @param PDO     $db      The database connection handle
     */
    public function __construct(PDO $db, Request $request) {
        $this->_db     = $db;
        $this->base    = $request->base;
        $this->version = $request->version;
    }

    /**
     * verifyAccessToken
     *
     * @param string $token The valid access token
     * @access public
     * @return int The ID of the user this belongs to
     */
    public function verifyAccessToken($token) {
        $sql = 'select id, user_id from oauth_access_tokens'
            . ' where access_token=:access_token';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("access_token" => $token));
        $result = $stmt->fetch();

        // log that we used this token
        $update_sql = 'update oauth_access_tokens '
            . ' set last_used_date = NOW()'
            . ' where id = :id';
        $update_stmt = $this->_db->prepare($update_sql);
        $update_stmt->execute(array("id" => $result['id']));

        // return the user ID this token belongs to
        return $result['user_id'];
    }

    /**
     * Create an access token for a username or password
     *
     * @param  string $clientId aka consumer_key
     * @param  string $username username
     * @param  string $password password
     * @return string           access token
     */
    public function createAccessTokenFromPassword($clientId, $username, $password)
    {
        // is the username/password combination correct?
        $userId = $this->getUserId($username, $password);
        if (!$userId) {
            return false;
        }

        // create new token
        $accessToken = $this->newAccessToken($clientId, $userId);

        // we also want to send back the logged in user's uri
        $userUri = $this->base . '/' . $this->version . '/users/' . $userId;

        return array('access_token' => $accessToken, 'user_uri' => $userUri);
    }

    /**
     * Retrieve the user's record from the database.
     *
     * @param  string $username user's username
     * @param  string $password user's password
     * @return mixed            user's id on success or false
     */
    protected function getUserId($username, $password)
    {
        $sql = 'SELECT ID, email FROM user
                WHERE username=:username AND password=:password';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("username" => $username, "password" => md5($password)));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return $result['ID'];
        }

        return $result;
    }

    /**
     * Expire any tokens belonging to the list of $clientIds that are over a day old.
     *
     * @param  array $clientIds list of client ids to expire
     * @return void
     */
    public function expireOldTokens($clientIds)
    {
        foreach ($clientIds as $clientId) {
            $sql = "DELETE FROM oauth_access_tokens WHERE
                    consumer_key=:consumer_key AND last_used_date < :expiry_date";

            $stmt = $this->_db->prepare($sql);
            $stmt->execute(array(
                'consumer_key' => $clientId,
                'expiry_date'  => date('Y-m-d', strtotime('-1 day'))
                ));
        }
    }

    /**
     * Generate, store and return a new access token to the user
     *
     * @param string $consumer_key the identifier for the consumer
     * @param int    $user_id the user granting access
     *
     * @return string access token
     */
    public function newAccessToken($consumer_key, $user_id)
    {
        $hash              = $this->generateToken();
        $accessToken       = substr($hash, 0, 16);
        $accessTokenSecret = substr($hash, 16, 16);

        $sql = "INSERT INTO oauth_access_tokens set
                access_token = :access_token,
                access_token_secret = :access_token_secret,
                consumer_key = :consumer_key,
                user_id = :user_id,
                last_used_date = NOW()
                ";

        $stmt = $this->_db->prepare($sql);
        $result = $stmt->execute(array(
            'access_token'        => $accessToken,
            'access_token_secret' => $accessTokenSecret,
            'consumer_key'        => $consumer_key,
            'user_id'             => $user_id,
            ));

        if ($result) {
            return $accessToken;
        }

        return false;
    }

    /**
     * generateToken
     *
     * taken mostly from
     * http://toys.lerdorf.com/archives/55-Writing-an-OAuth-Provider-Service.html
     *
     * @return string
     */
    public function generateToken()
    {
        $fp      = fopen('/dev/urandom', 'rb');
        $entropy = fread($fp, 32);
        fclose($fp);
        // in case /dev/urandom is reusing entropy from its pool,
        // let's add a bit more entropy
        $entropy .= uniqid(mt_rand(), true);
        $hash     = sha1($entropy); // sha1 gives us a 40-byte hash
        return $hash;
    }


    /**
     *  Get the name of the consumer that this user was authenticated with
     *
     * @param string $token The valid access token
     * @access public
     * @return string An identifier for the OAuth consumer
     */
    public function getConsumerName($token) {
        $sql = 'select at.consumer_key, c.id, c.application '
            . 'from oauth_access_tokens at '
            . 'left join oauth_consumers c using (consumer_key) '
            . 'where at.access_token=:access_token ';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("access_token" => $token));
        $result = $stmt->fetch();

        // what did we get? Might have been an oauth app, a special one (like web2)
        // or something else.
        if($result['application']) {
            return $result['application'];
        } else {
            return "joind.in";
        }
    }

    /**
     * Check whether a supplied consumer is permitted to use
     * the "password" grant type during the OAuth process
     *
     * @param string $key An OAuth consumer key to check
     * @param string $secret The corresponding consumer secret
     * @return bool Whether the consumer is permitted
     */
    public function isClientPermittedPasswordGrant($key, $secret) {
        $sql = 'select c.enable_password_grant from '
            . 'oauth_consumers c '
            . 'where c.consumer_key=:key '
            . 'and c.consumer_secret=:secret';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("key" => $key, "secret" => $secret));
        $result = $stmt->fetch();
        if ($result) {
            return $result['enable_password_grant'] == 1;
        }

        return false;
    }
}
