<?php

class TokenMapper extends ApiMapper
{
    /**
     * Iterate through results from the database to ensure data consistency and
     * add sub-resource data
     *
     * @param  array $results
     *
     * @return array
     */
    private function processResults($results)
    {
        if (!is_array($results)) {
            // $results isn't an array. This shouldn't happen as an exception
            // should have been raised by PDO. However if it does, return an
            // empty array.
            return [];
        }

        if (! count($results)) {
            // $results is an array but empty. So let's return an empty arra
            return [];
        }

        return $results;
    }

    /**
     * Get all tokens that are registered for a given user
     *
     * @param int $user_id          The user to fetch clients for
     * @param int $resultsperpage   How many results to return on each page
     * @param int $start            Which result to start with
     *
     * @return TokenModelCollection
     */
    public function getTokensForUser($user_id, $resultsperpage, $start)
    {
        $sql = 'SELECT a.id, b.application, a.created_date, a.last_used_date, c.full_name '
             . 'FROM oauth_access_tokens AS a '
             . 'LEFT JOIN oauth_consumers AS b on a.consumer_key=b.consumer_key '
             . 'LEFT JOIN `user` as c ON b.user_id = c.id '
             . 'WHERE a.user_id = :user_id ';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id' => $user_id
        ));

        if (! $response) {
            return false;
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $this->getTotalCount($sql, [':user_id' => $user_id]);
        $results = $this->processResults($results);

        return new TokenModelCollection($results, $total);
    }

    /**
     * Get all tokens that are registered for a given user that can be revoked
     *
     * @param int $user_id          The user to fetch clients for
     * @param int $resultsperpage   How many results to return on each page
     * @param int $start            Which result to start with
     *
     * @return TokenModelCollection
     */
    public function getRevokableTokensForUser($user_id, $resultsperpage, $start)
    {
        $sql = 'SELECT a.id, b.application, a.created_date, a.last_used_date, c.full_name '
               . 'FROM oauth_access_tokens AS a '
               . 'LEFT JOIN oauth_consumers AS b on a.consumer_key=b.consumer_key '
               . 'LEFT JOIN `user` as c ON b.user_id = c.id '
               . 'WHERE a.user_id = :user_id AND b.can_be_revoked = 1';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id' => $user_id
        ));

        if (! $response) {
            return false;
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $this->getTotalCount($sql, [':user_id' => $user_id]);
        $results = $this->processResults($results);

        return new TokenModelCollection($results, $total);
    }



    /**
     * Get a single token by id and user
     *
     * @param int $token_id The ID of the token
     * @param int $user_id  The user to fetch the token for
     *
     * @return TokenModelCollection
     */
    public function getTokenByIdAndUser($token_id, $user_id)
    {
        $sql = 'SELECT a.id, b.application, a.created_date, a.last_used_date, c.full_name '
               . 'FROM oauth_access_tokens AS a '
               . 'LEFT JOIN oauth_consumers AS b on a.consumer_key=b.consumer_key '
               . 'LEFT JOIN `user` as c ON b.user_id = c.id '
               . 'WHERE a.user_id = :user_id AND a.id = :token_id';
        $sql .= $this->buildLimit(1, 0);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id'  => $user_id,
            ':token_id' => $token_id,
        ));

        if (! $response) {
            return new TokenModelCollection([], 1);
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = $this->processResults($results);

        return new TokenModelCollection($results, 1);
    }


    /**
     * Get a single token by id and user
     *
     * @param int $token_id The ID of the token
     * @param int $user_id  The user to fetch the token for
     *
     * @return TokenModelCollection
     */
    public function getRevokableTokenByIdAndUser($token_id, $user_id)
    {
        $sql = 'SELECT a.id, b.application, a.created_date, a.last_used_date, c.full_name '
               . 'FROM oauth_access_tokens AS a '
               . 'LEFT JOIN oauth_consumers AS b on a.consumer_key=b.consumer_key '
               . 'LEFT JOIN `user` as c ON b.user_id = c.id '
               . 'WHERE a.user_id = :user_id AND a.id = :token_id AND b.can_be_revoked = 1';
        $sql .= $this->buildLimit(1, 0);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':user_id'  => $user_id,
            ':token_id' => $token_id,
        ));

        if (! $response) {
            return new TokenModelCollection([], 1);
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = $this->processResults($results);

        return new TokenModelCollection($results, 1);
    }

    /**
     * Delete an existing token
     *
     * @param int $tokenId
     *
     * @throws Exception
     * @return bool
     */
    public function deleteToken($tokenId)
    {
        $tokenSql = 'DELETE FROM oauth_access_tokens WHERE id = :token_id';

        $stmt = $this->_db->prepare($tokenSql);

        if (! $stmt->execute([':token_id' => $tokenId])) {
            throw new Exception('There has been an error removing the token');
        }

        return true;
    }
}
