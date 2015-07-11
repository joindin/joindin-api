<?php

class TwitterRequestTokenMapper extends ApiMapper
{
    public function create($token, $secret)
    {
        $sql = 'insert into twitter_request_tokens '
            . 'set token=:token, secret=:secret';
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':token' => $token,
            ':secret' => $secret
            ));
        if ($response) {
            $token_id = $this->_db->lastInsertId();

            $select_sql = "select ID, token, secret from twitter_request_tokens "
                . "where ID = :id";
            $select_stmt = $this->_db->prepare($select_sql);
            $select_stmt->execute(array(":id" => $token_id));
            $token_data = $select_stmt->fetch(PDO::FETCH_ASSOC);
            $token = new TwitterRequestTokenModelCollection([$token_data]);
            return $token;
        }
        return false;
    }

    public function delete($token)
    {
        $sql = 'delete from twitter_request_tokens '
            . 'where token=:token';
        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute(array(
            ':token' => $token,
            ));
        return true;
    }
}
