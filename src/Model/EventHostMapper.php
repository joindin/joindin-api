<?php

namespace Joindin\Api\Model;

use PDO;

class EventHostMapper extends ApiMapper
{
    public function getDefaultFields(): array
    {
        return [
            'host_name' => 'host_name',
            'host_uri'  => 'host_uri',
        ];
    }

    public function getVerboseFields(): array
    {
        return $this->getDefaultFields();
    }

    /**
     * @param int  $event_id
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getHostsByEventId(int $event_id, int $resultsperpage, int $start, bool $verbose = false): false|array
    {
        $sql = $this->getHostSql();
        $sql .= ' order by host_name ';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute([
            ':event_id' => $event_id
        ]);

        if (!$response) {
            return false;
        }

        $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['total'] = $this->getTotalCount($sql, [':event_id' => $event_id]);

        return $this->transformResults($results, $verbose);
    }

    /**
     * @param int $event_id
     * @param int $host_id
     *
     * @return false|string
     */
    public function addHostToEvent($event_id, $host_id)
    {
        $sql  = 'INSERT INTO user_admin (uid, rid, rtype) VALUES (:host_id, :event_id, :type)';
        $stmt = $this->_db->prepare($sql);

        $response = $stmt->execute([
            ':host_id'  => $host_id,
            ':event_id' => $event_id,
            ':type'     => 'event',
        ]);

        if (!$response) {
            return false;
        }

        return $this->_db->lastInsertId();
    }

    /**
     * @param int $host_id
     * @param int $event_id
     *
     * @return bool
     */
    public function removeHostFromEvent($host_id, $event_id)
    {
        $sql  = 'DELETE FROM user_admin WHERE uid = :user_id AND rid = :event_id AND rtype = :type';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute([
            ':user_id'  => $host_id,
            ':event_id' => $event_id,
            ':type'     => 'event',
        ]);
    }

    /**
     * SQL for fetching event hosts, so it can be used in multiple places
     *
     * @return string SQL to fetch hosts, containing an :event_id named parameter
     */
    protected function getHostSql()
    {
        return 'select a.uid as user_id, u.full_name as host_name '
               . 'from user_admin a '
               . 'inner join user u on u.ID = a.uid '
               . 'where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null)';
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
        if (!is_array($list)) {
            return [];
        }

        if (1 > count($list)) {
            return [];
        }

        foreach ($results as $key => $row) {
            // generate and store an inflected event name if there isn't one already
            $list[$key]['host_name'] = $row['host_name'];
            $list[$key]['host_uri']  = $base . '/' . $version . '/users/' . $row['user_id'];
        }

        return [
            'hosts' => $list,
            'meta'  => $this->getPaginationLinks($list, $total),
        ];
    }
}
