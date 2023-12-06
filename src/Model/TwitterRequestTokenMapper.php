<?php

namespace Joindin\Api\Model;

use PDO;
use Teapot\StatusCode\Http;

class TwitterRequestTokenMapper extends ApiMapper
{
    /**
     * @param string $token
     * @param string $secret
     *
     * @return TwitterRequestTokenModelCollection
     */
    public function create($token, $secret): TwitterRequestTokenModelCollection
    {
        $sql      = 'insert into twitter_request_tokens '
                    . 'set token=:token, secret=:secret';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':token'  => $token,
            ':secret' => $secret
        ]);

        if (!$response) {
            throw new \Exception('Unable to create token', Http::BAD_REQUEST);
        }

        $token_id = $this->_db->lastInsertId();

        $select_sql  = "select ID, token, secret from twitter_request_tokens "
                       . "where ID = :id";
        $select_stmt = $this->_db->prepare($select_sql);
        $select_stmt->execute([":id" => $token_id]);
        $token_data = $select_stmt->fetch(PDO::FETCH_ASSOC);

        return new TwitterRequestTokenModelCollection([$token_data]);
    }

    /**
     * @param string $token
     *
     * @return bool
     */
    public function delete($token)
    {
        $sql      = 'delete from twitter_request_tokens '
                    . 'where token=:token';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':token' => $token,
        ]);

        return true;
    }
}
