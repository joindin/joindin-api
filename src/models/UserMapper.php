<?php

/**
 * UserMapper
 * 
 * @uses ApiModel
 * @package API
 */
class UserMapper extends ApiMapper 
{

    /**
     * Default mapping for column names to API field names
     * 
     * @return array with keys as API fields and values as db columns
     */
    public function getDefaultFields() 
    {
        $fields = array(
            "username" => "username",
            "full_name" => "full_name",
            "twitter_username" => "twitter_username"
            );
        return $fields;
    }

    /**
     * Field/column name mappings for the verbose version
     *
     * This should contain everything above and then more in most cases
     * 
     * @return array with keys as API fields and values as db columns
     */
    public function getVerboseFields() 
    {
        $fields = array(
            "username" => "username",
            "full_name" => "full_name",
            "twitter_username" => "twitter_username",
            );
        return $fields;
    }

    public function getUserById($user_id, $verbose = false) 
    {
        $results = $this->getUsers(1, 0, 'user.ID=' . (int)$user_id, null);
        if ($results) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;

    }

    public function getUserByUsername($username, $verbose = false)
    {
        $sql = 'select user.* '
            . 'from user '
            . 'where active = 1 '
            . 'and user.username = :username';

        // limit clause
        $sql .= $this->buildLimit(1, 0);

        $stmt = $this->_db->prepare($sql);
        $data = array("username" => $username);

        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);
            if ($results) {
                return $this->transformResults($results, $verbose);
            }
        }
        return false;
    }

    public function getSiteAdminEmails()
    {
        $sql = 'select email from user where admin = 1';
        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute();
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results;
        }
        return false;
    }

    protected function getUsers($resultsperpage, $start, $where = null, $order = null)
    {
        $sql = 'select user.username, user.ID, user.email, '
            . 'user.full_name, user.twitter_username, user.admin '
            . 'from user '
            . 'left join user_attend ua on (ua.uid = user.ID) '
            . 'where active = 1 ';
        
        // where
        if ($where) {
            $sql .= ' and ' . $where;
        }

        // group by because of adding the user_attend join
        $sql .= ' group by user.ID ';

        // order by
        if ($order) {
            $sql .= ' order by ' . $order;
        }

        // limit clause
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute();
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql);
            return $results;
        }
        return false;
    }

    public function getUserList($resultsperpage, $start, $verbose = false) 
    {
        $order = 'user.ID';
        $results = $this->getUsers($resultsperpage, $start, null, $order);
        if (is_array($results)) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    public function getUsersAttendingEventId($event_id, $resultsperpage, $start, $verbose)
    {
        $where = "ua.eid = " . $event_id;
        $results = $this->getUsers($resultsperpage, $start, $where);
        if (is_array($results)) {
            $retval = $this->transformResults($results, $verbose);
            return $retval;
        }
        return false;
    }

    public function transformResults($results, $verbose) 
    {
        $total = $results['total'];
        unset($results['total']);

        $list = parent::transformResults($results, $verbose);
        $base = $this->_request->base;
        $version = $this->_request->version;

        // add per-item links 
        if(is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                if (true === $verbose) {
                    $list[$key]['gravatar_hash'] = md5(strtolower($row['email']));
                }
                $list[$key]['uri'] = $base . '/' . $version . '/users/' 
                    . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/users/' 
                    . $row['ID'] . '?verbose=yes';
                $list[$key]['website_uri'] = 'http://joind.in/user/view/' . $row['ID'];
                $list[$key]['talks_uri'] = $base . '/' . $version . '/users/' 
                    . $row['ID'] . '/talks/';
                $list[$key]['attended_events_uri'] = $base . '/' . $version . '/users/'
                    . $row['ID'] . '/attended/';
                $list[$key]['hosted_events_uri'] = $base . '/' . $version . '/users/'
                    . $row['ID'] . '/hosted/';
                $list[$key]['talk_comments_uri'] = $base . '/' . $version . '/users/'
                    . $row['ID'] . '/talk_comments/';
            }
        }
        $retval = array();
        $retval['users'] = $list;
        $retval['meta'] = $this->getPaginationLinks($list, $total);

        return $retval;
    }


    public function isSiteAdmin($user_id) {
        $results = $this->getUsers(1, 0, 'user.ID=' . (int)$user_id, null);
        if($results[0]['admin'] == 1) {
            return true;
        }
        return false;
    }

    public function createUser($user) {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields = array(
            'username',
            'full_name',
            'email',
            'password',
        );
        $contains_mandatory_fields = !array_diff($mandatory_fields, array_keys($user));
        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        // encode the password
        $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);

        $sql = "insert into user set active=1, admin=0, ";

        // create list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        // add the fields that we don't expose for users
        $fields["email"] = "email";
        $fields["password"] = "password";

        foreach ($fields as $api_name => $column_name) {
            if (isset($user[$api_name])) {
                $pairs[] = "$column_name = :$api_name";
            }
        }

        // comma separate all pairs and add to SQL string
        $sql .= implode(', ', $pairs);

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute($user);
        if($result) {
            return $this->_db->lastInsertId();
        }

        return false;
    }

    public function getUserByEmail($email, $verbose = false)
    {
        $sql = 'select user.* '
            . 'from user '
            . 'where active = 1 '
            . 'and user.email= :email';

        // limit clause
        $sql .= $this->buildLimit(1, 0);

        $stmt = $this->_db->prepare($sql);
        $data = array("email" => $email);

        $response = $stmt->execute($data);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);
            if ($results) {
                return $this->transformResults($results, $verbose);
            }
        }
        return false;
    }

    /**
     * Use this method to check password against our rules.
     *
     * Beware that it returns true or an array, so you need === true
     *
     * @param string $password The password to check (plain text)
     * @return bool|array Either true if it's fine, or an array of remarks about why it isn't
     */
    public function checkPasswordValidity($password) {
        $errors = array();
        if(strlen($password) < 6) {
            $errors[] = "Passwords must be at least 6 characters long";
        }

        if($errors) {
            return $errors;
        }

        // it's good!
        return true;

    }


    /**
     * Generate and store a token in the email_verification_tokens table for this 
     * user, when they use the token to verify, we'll set their status to verified
     */
    public function generateEmailVerificationTokenForUserId($user_id) {
        $token = bin2hex(openssl_random_pseudo_bytes(8));

        $sql = "insert into email_verification_tokens set "
            . "user_id = :user_id, token = :token";

        $stmt = $this->_db->prepare($sql);
        $data = array(
            "user_id" => $user_id, 
            "token" => $token);

        $response = $stmt->execute($data);
        if ($response) {
            return $token;
        }

        return false;

    }

    /**
     * Given a token, if it exists, delete it and return true
     *
     * @param string $token     The email verification token
     * @return bool             True if the token was found
     */
    public function verifyUser($token) {
        // does the token exist, and whose is it?
        $select_sql = "select user_id from email_verification_tokens "
            . "where token = :token";

        $select_stmt = $this->_db->prepare($select_sql);
        $data = array(
            "token" => $token);

        $response = $select_stmt->execute($data);
        if($response) {
            $row = $select_stmt->fetch(\PDO::FETCH_ASSOC);
            if($row && is_array($row)) {
                $user_id = $row['user_id'];

                // mark the user as verified
                $verify_sql = "update user set verified = 1 "
                    . "where ID = :user_id";

                $verify_stmt = $this->_db->prepare($verify_sql);
                $verify_data = array("user_id" => $user_id);

                $verify_stmt->execute($verify_data);

                // now delete the token so it can't be reused
                $delete_sql = "delete from email_verification_tokens "
                    . "where token = :token";

                $stmt = $this->_db->prepare($delete_sql);
                $stmt->execute($data);

                // verified
                return true;
            }
        }

        return false;
    }
}
