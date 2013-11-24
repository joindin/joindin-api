<?php

class OAuthModel {

    /**
     * Object constructor, sets up the db and some objects need request too
     * 
     * @param PDO     $db      The database connection handle
     */
    public function __construct(PDO $db) {
        $this->_db = $db;
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
     * getConsumerInfo
     *
     * @param string $token The valid access token
     * @access public
     * @return array An array of data describing the consumer
     */
    public function getConsumerInfo($token) {
        $sql = 'select c.* from oauth_consumers c'
            . ' inner join oauth_access_tokens t USING (consumer_key)'
            . ' where t.access_token=:access_token';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array("access_token" => $token));
        $result = $stmt->fetch();

        return $result;
    }
}
