<?php

namespace Joindin\Api\Model;

use Exception;
use PDO;

/**
 * UserMapper
 *
 * @uses    ApiModel
 * @package API
 */
class UserMapper extends ApiMapper
{
    /**
     * Default mapping for column names to API field names
     *
     * @return array with keys as API fields and values as db columns
     */
    public function getDefaultFields(): array
    {
        return [
            "username"         => "username",
            "full_name"        => "full_name",
            "biography"        => "biography",
            "twitter_username" => "twitter_username"
        ];
    }

    /**
     * Field/column name mappings for the verbose version
     *
     * This should contain everything above and then more in most cases
     *
     * @return array with keys as API fields and values as db columns
     */
    public function getVerboseFields(): array
    {
        return [
            "username"         => "username",
            "full_name"        => "full_name",
            "twitter_username" => "twitter_username",
            "biography"        => "biography",
            "trusted"          => "trusted"
        ];
    }

    /**
     * @param int  $user_id
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getUserById($user_id, $verbose = false)
    {
        $results = $this->getUsers(1, 0, 'user.ID=' . (int) $user_id, null);

        if ($results) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param string $username
     * @param bool   $verbose
     *
     * @return false|array
     */
    public function getUserByUsername($username, $verbose = false)
    {
        $sql = 'select user.* '
               . 'from user '
               . 'where active = 1 '
               . 'and user.username = :username';

        // limit clause
        $sql .= $this->buildLimit(1, 0);

        $stmt = $this->_db->prepare($sql);
        $data = ["username" => $username];

        $response = $stmt->execute($data);

        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);

            if ($results) {
                return $this->transformResults($results, $verbose);
            }
        }

        return false;
    }

    /**
     * Search users by keyword
     *
     * @param string $keyword
     * @param int    $resultsperpage
     * @param int    $start
     * @param bool   $verbose
     *
     * @return array|bool Result collection or false on failure
     */
    public function getUserByKeyword($keyword, $resultsperpage, $start, $verbose)
    {
        $sql = 'select user.* '
               . 'from user '
               . 'where active = 1 '
               . ' and ( '
               . ' LOWER(user.username) like :keyword'
               . ' or LOWER(user.full_name) like :keyword'
               . ' or LOWER(user.twitter_username) like :keyword'
               . ') '
               . ' order by user.full_name asc';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $data = [
            ':keyword' => '%' . strtolower($keyword) . '%',
        ];

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);

        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $data);

            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @return false|array
     */
    public function getSiteAdminEmails()
    {
        $sql  = 'select email from user where admin = 1';
        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute();

        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return $results;
        }

        return false;
    }

    /**
     * @param int         $resultsperpage
     * @param int         $start
     * @param string|null $where
     * @param string|null $order
     *
     * @return false|array
     */
    protected function getUsers($resultsperpage, $start, $where = null, $order = null)
    {
        $sql = 'select user.username, user.ID, user.email, '
               . 'user.full_name, user.biography, user.twitter_username, user.admin, user.trusted '
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
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql);

            return $results;
        }

        return false;
    }

    /**
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getUserList($resultsperpage, $start, $verbose = false)
    {
        $order   = 'user.ID';
        $results = $this->getUsers($resultsperpage, $start, null, $order);

        if (is_array($results)) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param int  $event_id
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getUsersAttendingEventId($event_id, $resultsperpage, $start, $verbose)
    {
        $where   = "ua.eid = " . $event_id;
        $results = $this->getUsers($resultsperpage, $start, $where);

        if (is_array($results)) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function transformResults(array $results, $verbose): array
    {
        $total = $results['total'];
        unset($results['total']);

        $list    = parent::transformResults($results, $verbose);
        $base    = $this->_request->base;
        $version = $this->_request->version;

        // add per-item links
        if (is_array($list) && count($list)) {
            $userIsSiteAdmin = false;

            if ($this->_request->user_id && $this->isSiteAdmin($this->_request->user_id)) {
                $userIsSiteAdmin = true;
            }

            foreach ($results as $key => $row) {
                // can the logged in user edit this user?
                $canEdit = false;

                if ($userIsSiteAdmin || $row['ID'] == $this->_request->user_id) {
                    $canEdit = true;
                }

                if (true === $verbose) {
                    $list[$key]['gravatar_hash'] = md5(strtolower($row['email']));

                    // if this record can be edited, then expose the email address
                    if ($canEdit) {
                        $list[$key]['email'] = $row['email'];
                    }

                    // expose the admin field if the currently logged in user is a site admin
                    if ($userIsSiteAdmin) {
                        $list[$key]['admin'] = $row['admin'];
                    }
                }
                $list[$key]['uri']                 = $base . '/' . $version . '/users/' . $row['ID'];
                $list[$key]['verbose_uri']         = $base . '/' . $version . '/users/' . $row['ID'] . '?verbose=yes';
                $list[$key]['website_uri']         = $this->website_url . '/user/' . $row['username'];
                $list[$key]['talks_uri']           = $base . '/' . $version . '/users/' . $row['ID'] . '/talks/';
                $list[$key]['attended_events_uri'] = $base . '/' . $version . '/users/' . $row['ID'] . '/attended/';
                $list[$key]['hosted_events_uri']   = $base . '/' . $version . '/users/' . $row['ID'] . '/hosted/';
                $list[$key]['talk_comments_uri']   = $base . '/' . $version . '/users/' . $row['ID']
                                                     . '/talk_comments/';

                if ($verbose && isset($this->_request->user_id)) {
                    $list[$key]['can_edit'] = $canEdit;
                }
            }
        }

        return [
            'users' => $list,
            'meta'  => $this->getPaginationLinks($list, $total),
        ];
    }

    /**
     * @param int $user_id
     *
     * @return bool
     */
    public function isSiteAdmin($user_id)
    {
        $results = $this->getUsers(1, 0, 'user.ID=' . (int) $user_id, null);

        if (isset($results[0]) && $results[0]['admin'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user represented by $user_id is trusted
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function isTrusted($user_id)
    {
        $results = $this->getUsers(1, 0, 'user.ID=' . (int) $user_id, null);

        if (isset($results[0]) && $results[0]['trusted'] == 1) {
            return true;
        }

        return false;
    }

    /**
     * Update the trusted status for the given user
     *
     * @param bool $trustedStatus
     * @param int  $user_id
     *
     * @return bool
     */
    public function setTrustedStatus($trustedStatus, $user_id)
    {
        $verify_sql = "update user set trusted = :trusted_status "
                      . "where ID = :user_id";

        $verify_stmt = $this->_db->prepare($verify_sql);
        $verify_data = ["trusted_status" => (int) $trustedStatus, "user_id" => $user_id];

        return $verify_stmt->execute($verify_data);
    }

    /**
     * @param array $user
     *
     * @throws Exception
     * @return false|string user ID
     */
    public function createUser($user)
    {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields          = [
            'username',
            'full_name',
            'email',
            'password',
        ];
        $contains_mandatory_fields = ! array_diff($mandatory_fields, array_keys($user));

        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        // encode the password
        $user['password'] = password_hash(md5($user['password']), PASSWORD_DEFAULT);

        $sql = "insert into user set active=1, admin=0, ";

        // create list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();
        // add the fields that we don't expose for users
        $fields["email"]    = "email";
        $fields["password"] = "password";

        $pairs = [];

        foreach ($fields as $api_name => $column_name) {
            if (isset($user[$api_name])) {
                $pairs[] = "$column_name = :$api_name";
            }
        }

        // comma separate all pairs and add to SQL string
        $sql .= implode(', ', $pairs);

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute($user);

        if ($result) {
            return $this->_db->lastInsertId();
        }

        return false;
    }

    /**
     * @param string $email
     * @param bool   $verbose
     *
     * @return false|array
     */
    public function getUserByEmail($email, $verbose = false)
    {
        $sql = 'select user.* '
               . 'from user '
               . 'where active = 1 '
               . 'and user.email= :email';

        // limit clause
        $sql .= $this->buildLimit(1, 0);

        $stmt = $this->_db->prepare($sql);
        $data = ["email" => $email];

        $response = $stmt->execute($data);

        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     *
     * @return true|array Either true if it's fine, or an array of remarks about why it isn't
     */
    public function checkPasswordValidity($password)
    {
        if (strlen($password) < 6) {
            return ["Passwords must be at least 6 characters long"];
        }

        // it's good!
        return true;
    }

    /**
     * Generate and store a token in the email_verification_tokens table for this
     * user, when they use the token to verify, we'll set their status to verified
     *
     * @param int $user_id
     *
     * @return false|string token
     */
    public function generateEmailVerificationTokenForUserId($user_id)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(8));

        $sql = "insert into email_verification_tokens set "
               . "user_id = :user_id, token = :token";

        $stmt = $this->_db->prepare($sql);
        $data = [
            "user_id" => $user_id,
            "token"   => $token
        ];

        $response = $stmt->execute($data);

        if ($response) {
            return $token;
        }

        return false;
    }

    /**
     * Given a token, if it exists, delete it and return true
     *
     * @param string $token The email verification token
     *
     * @return bool             True if the token was found
     */
    public function verifyUser($token)
    {
        // does the token exist, and whose is it?
        $select_sql = "select user_id from email_verification_tokens "
                      . "where token = :token";

        $select_stmt = $this->_db->prepare($select_sql);
        $data        = [
            "token" => $token
        ];

        $response = $select_stmt->execute($data);

        if ($response) {
            $row = $select_stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && is_array($row)) {
                $user_id = $row['user_id'];

                // mark the user as verified
                $this->markUserVerified($user_id);

                // delete all the user's tokens; they don't need them now
                $delete_sql = "delete from email_verification_tokens "
                              . "where user_id = :user_id";

                $stmt = $this->_db->prepare($delete_sql);
                $stmt->execute(['user_id' => $user_id]);

                // verified
                return true;
            }
        }

        return false;
    }

    /**
     * Function to get just the user ID
     *
     * @param string $email The email address of the user we're looking for
     *
     * @return false|int $user_id The user's ID (or false, if we didn't find her)
     */
    public function getUserIdFromEmail($email)
    {
        $sql = "select ID from user "
               . "where email = :email and active = 1";

        $data     = ["email" => $email];
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);

        if ($response) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (isset($row['ID'])) {
                return $row['ID'];
            }
        }

        return false;
    }

    /**
     * Used only on test platforms, if the config is enabled
     *
     * Designed to allow creation of verified users for testing purposes
     *
     * @param int $user_id
     *
     * @return bool
     */
    public function verifyThisTestUser($user_id)
    {
        return $this->markUserVerified($user_id);
    }

    /**
     * @param int $user_id
     *
     * @return bool
     */
    protected function markUserVerified($user_id)
    {
        $verify_sql = "update user set verified = 1 "
                      . "where ID = :user_id";

        $verify_stmt = $this->_db->prepare($verify_sql);
        $verify_data = ["user_id" => $user_id];

        $verify_stmt->execute($verify_data);

        return true;
    }

    /**
     * Does the currently-authenticated user have right to edit this user?
     *
     * User must be the edited user, or a site admin
     *
     * @param int $user_id The identifier for the user to edit
     *
     * @return bool if the user has privileges
     */
    public function thisUserHasAdminOn($user_id)
    {
        // do we even have an authenticated user?
        $loggedInUser = $this->_request->getUserId();

        if ($loggedInUser) {
            // are we asking for access to the current user?
            if ($loggedInUser == $user_id) {
                // user can edit themselves
                return true;
            }

            // is this a site admin?
            if ($this->isSiteAdmin($loggedInUser)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update an existing user record
     *
     * @param array $user   An array of fields to change
     * @param int   $userId The user to update
     *
     * @throws Exception
     * @return bool if successful
     */
    public function editUser(array $user, $userId)
    {
        // Sanity check: ensure all mandatory fields are present.
        $mandatory_fields          = [
            'full_name',
            'email',
        ];
        $contains_mandatory_fields = ! array_diff($mandatory_fields, array_keys($user));

        if (!$contains_mandatory_fields) {
            throw new Exception("Missing mandatory fields");
        }

        // create list of column to API field name for all valid fields
        $fields = $this->getVerboseFields();

        // encode the password
        if (isset($user['password'])) {
            $user['password']   = password_hash(md5($user['password']), PASSWORD_DEFAULT);
            $fields["password"] = "password";
        }

        $sql = "update user set ";

        // add the fields that we don't expose for users
        $fields["email"] = "email";

        $pairs = [];

        foreach ($fields as $api_name => $column_name) {
            if (isset($user[$api_name])) {
                $pairs[] = "$column_name = :$api_name";
            }
        }

        // comma separate all pairs and add to SQL string
        $sql .= implode(', ', $pairs);
        $sql .= " where ID = :user_id ";

        $stmt   = $this->_db->prepare($sql);
        $result = $stmt->execute($user);

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * Function to get just the user ID
     *
     * @param string $username The username of the user we're looking for
     *
     * @return false|int $user_id The user's ID (or false, if we didn't find her)
     */
    public function getUserIdFromUsername($username)
    {
        $sql = "select ID from user where username = :username";

        $data     = ["username" => $username];
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($data);

        if ($response) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (isset($row['ID'])) {
                return $row['ID'];
            }
        }

        return false;
    }

    /**
     * We don't expose the email address in resources, but sometimes we need
     * to email users, so this is how to get the email address
     *
     * @param int $user_id The ID of the user
     *
     * @return false|string $email The email address
     */
    public function getEmailByUserId($user_id)
    {
        $sql      = "select email from user where ID = :user_id";
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(["user_id" => $user_id]);

        if ($response) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (isset($row['email'])) {
                return $row['email'];
            }
        }

        return false;
    }

    /**
     * Generate and store a token in the password_reset_tokens table for this
     * user, when they come to web2 with this token, we'll let them set a new
     * password
     *
     * @param int $user_id
     *
     * @return false|string
     */
    public function generatePasswordResetTokenForUserId($user_id)
    {
        $token = bin2hex(openssl_random_pseudo_bytes(8));

        $sql = "insert into password_reset_tokens set "
               . "user_id = :user_id, token = :token";

        $stmt = $this->_db->prepare($sql);
        $data = [
            "user_id" => $user_id,
            "token"   => $token
        ];

        $response = $stmt->execute($data);

        if ($response) {
            return $token;
        }

        return false;
    }

    /**
     * When the user forgets their password, we generate and send them a token.
     * Check that the token is valid, find out which user this is, save their
     * new password and then delete their other tokens
     *
     * @param string $token
     * @param string $password
     *
     * @return bool
     */
    public function resetPassword($token, $password)
    {
        // does the token exist, and whose is it?
        $select_sql = "select user_id from password_reset_tokens "
                      . "where token = :token";

        $select_stmt = $this->_db->prepare($select_sql);
        $data        = ["token" => $token];

        $response = $select_stmt->execute($data);

        if ($response) {
            $row = $select_stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && is_array($row)) {
                $user_id = $row['user_id'];

                // save the new password
                $update_sql = "update user set password = :password "
                              . "where ID = :user_id";

                $update_stmt     = $this->_db->prepare($update_sql);
                $update_data     = [
                    "password" => password_hash(md5($password), PASSWORD_DEFAULT),
                    "user_id"  => $user_id
                ];
                $update_response = $update_stmt->execute($update_data);

                if ($update_response) {
                    // delete all the user's tokens; they don't need them now
                    $delete_sql = "delete from password_reset_tokens "
                                  . "where user_id = :user_id";

                    $stmt = $this->_db->prepare($delete_sql);
                    $stmt->execute(["user_id" => $user_id]);

                    // all good
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int $user_id
     *
     * @return bool
     */
    public function delete($user_id)
    {
        try {
            $this->_db->beginTransaction();
        } catch (Exception $e) {
            // An Exception is thrown when a transaction is currently open or the
            // underlying database doesn't understand transactions. Shouldn't happen
            // but to be on the safe side.
        }

        try {
            // Delete the user
            $sql  = "delete from user where ID = :user_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["user_id" => $user_id]);

            // Unassign any talks
            $sql  = "update talk_speaker SET speaker_id = 0, status = NULL WHERE speaker_id = :speaker_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["speaker_id" => $user_id]);

            // Remove any pending talk claims
            $sql  = "delete from pending_talk_claims where speaker_id = :speaker_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["speaker_id" => $user_id]);

            // Anonymise any comments
            $sql  = "update talk_comments SET user_id = 0 WHERE user_id = :user_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["user_id" => $user_id]);

            // Remove any starred talks
            $sql  = "delete from user_talk_star where uid = :user_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["user_id" => $user_id]);

            // Remove user attendence
            $sql  = "delete from user_attend where uid = :user_id";
            $stmt = $this->_db->prepare($sql);
            $stmt->execute(["user_id" => $user_id]);

            $this->_db->commit();

            return true;
        } catch (Exception $e) {
            $this->_db->rollBack();

            return false;
        }
    }
}
