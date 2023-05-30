<?php

namespace Joindin\Api\Model;

use DateTime;
use DateTimeZone;
use Exception;
use PDO;

class ClientMapper extends ApiMapper
{
    /**
     * Iterate through results from the database to ensure data consistency and
     * add sub-resource data
     *
     * @param  array $results
     */
    private function processResults($results): array
    {
        if (!is_array($results)) {
            // $results isn't an array. This shouldn't happen as an exception
            // should have been raised by PDO. However, if it does, return an
            // empty array.
            return [];
        }

        if (!count($results)) {
            // $results is an array but empty. So let's return an empty array
            return [];
        }

        return $results;
    }

    /**
     * Get all clients that are registered for a given user
     *
     * @param int $user_id        The user to fetch clients for
     * @param int $resultsperpage How many results to return on each page
     * @param int $start          Which result to start with
     *
     * @return ClientModelCollection
     */
    public function getClientsForUser(int $user_id, int $resultsperpage, int $start): ClientModelCollection
    {
        $sql = 'SELECT * FROM oauth_consumers WHERE user_id = :user_id ';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':user_id' => $user_id
        ]);

        if (!$response) {
            throw new \RuntimeException('Database call failed');
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total   = $this->getTotalCount($sql, [':user_id' => $user_id]);
        $results = $this->processResults($results);

        return new ClientModelCollection($results, $total);
    }

    /**
     * Get a specific client with given ID and user
     *
     * @param string $clientId
     * @param string $userId
     *
     * @return ClientModelCollection
     */
    public function getClientByIdAndUser(string|int|false|null $clientId, string|int|false|null $userId): ClientModelCollection
    {
        if (! $clientId || ! $userId) {
            return new ClientModelCollection([], 0);
        }

        $sql = 'SELECT * FROM oauth_consumers WHERE user_id = :user_id and id = :client_id';
        $sql .= $this->buildLimit(1, 0);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':user_id'   => $userId,
            ':client_id' => $clientId,
        ]);

        if (!$response) {
            throw new \RuntimeException('Database call failed');
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = $this->processResults($results);

        return new ClientModelCollection($results, 1);
    }

    /**
     * Create a new client and return the new ID
     *
     * @param array $data
     *
     * @throws Exception
     * @return string
     */
    public function createClient(array $data): string
    {
        $clientSql = 'INSERT INTO oauth_consumers (consumer_key, consumer_secret,'
                     . 'created_date, user_id, application, description, '
                     . 'callback_url, enable_password_grant) VALUES (:consumer_key, '
                     . ':consumer_secret, :created_date, :user_id, :application, '
                     . ':description, :callback_url, :enable_password_grant);';

        $stmt = $this->_db->prepare($clientSql);
        $stmt->execute([
            ':consumer_key'          => base64_encode(random_bytes(48)),
            ':consumer_secret'       => base64_encode(random_bytes(48)),
            ':created_date'          => (new DateTime())->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            ':user_id'               => $data['user_id'],
            ':application'           => $data['name'],
            ':description'           => $data['description'],
            ':callback_url'          => $data['callback_url'],
            ':enable_password_grant' => 1,
        ]);

        $clientId = $this->_db->lastInsertId();

        if ($clientId === false || (int) $clientId === 0) {
            throw new Exception('There has been an error storing the application');
        }

        return $clientId;
    }

    /**
     * Update an existing Client
     *
     * @param string $clientId
     * @param array $data
     *
     * @throws Exception
     * @return string
     */
    public function updateClient(string $clientId, array $data): string
    {
        $clientSql = 'UPDATE oauth_consumers SET '
                     . 'application = :application, description = :description, '
                     . 'callback_url = :callback_url WHERE id = :client_id;';

        $stmt = $this->_db->prepare($clientSql);

        $result = $stmt->execute([
            ':application'  => $data['name'],
            ':description'  => $data['description'],
            ':callback_url' => $data['callback_url'],
            ':client_id'    => $clientId,
        ]);

        if (!$result) {
            throw new Exception('There has been an error updating the application');
        }

        return $clientId;
    }

    /**
     * Delete an existing client
     *
     * @param int $clientId
     *
     * @throws Exception
     */
    public function deleteClient(int $clientId): void
    {
        $clientSql = 'DELETE FROM oauth_consumers WHERE id = :client_id';

        $stmt = $this->_db->prepare($clientSql);

        if (!$stmt->execute([':client_id' => $clientId])) {
            throw new Exception('There has been an error updating the application');
        }
    }
}
