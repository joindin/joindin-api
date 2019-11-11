<?php

namespace Joindin\Api\Model;

use DateTime;
use PDO;

/**
 * PendingTalkClaimMapper
 *
 * @uses    ApiModel
 * @package API
 */
class PendingTalkClaimMapper extends ApiMapper
{
    public const SPEAKER_CLAIM = 1;
    public const HOST_ASSIGN = 2;

    /**
     * Default mapping for column names to API field names
     *
     * @return array with keys as API fields and values as db columns
     */
    public function getDefaultFields()
    {
        return [
            "talk_id"    => "username",
            "speaker_id" => "full_name",
        ];
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
        return [
            "talk_id"          => "username",
            "speaker_id"       => "full_name",
            "date_added"       => "date_added",
            "claim_id"         => "claim_id",
            "user_approved_at" => "user_approved_at",
            "host_approved_at" => "host_approved_at",
        ];
    }

    /**
     * Propose a talk relationship by the speaker
     *
     * @param int $talk_id    The ID of the talk to claim
     * @param int $speaker_id The ID of the speaker claiming the talk
     * @param int $claim_id   The ID from the talk_speaker table relating to the talk and display_name
     *
     * @return bool
     */
    public function claimTalkAsSpeaker($talk_id, $speaker_id, $claim_id)
    {
        $sql  = 'insert into pending_talk_claims 
                    (talk_id,submitted_by,speaker_id,date_added,claim_id,user_approved_at) 
                  values
                    (:talk_id,:submitted_by,:speaker_id,UNIX_TIMESTAMP(),:claim_id,NOW())
                 ';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            [
                'talk_id'      => $talk_id,
                'submitted_by' => $speaker_id,
                'speaker_id'   => $speaker_id,
                'claim_id'     => $claim_id
            ]
        );
    }

    /**
     * Propose a talk relationship by the host
     *
     * @param int $talk_id    The ID of the talk to claim
     * @param int $speaker_id The ID of the speaker who owns the talk
     * @param int $claim_id   The ID from the talk_speaker table relating to the talk and display_name
     * @param int $user_id    The ID of the user proposing the relationship
     *
     * @return bool
     */
    public function assignTalkAsHost($talk_id, $speaker_id, $claim_id, $user_id)
    {
        $sql  = 'insert into pending_talk_claims 
                    (talk_id,submitted_by,speaker_id,date_added,claim_id,host_approved_at) 
                  values
                    (:talk_id,:submitted_by,:speaker_id,UNIX_TIMESTAMP(),:claim_id,NOW())
                 ';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            [
                'talk_id'      => $talk_id,
                'submitted_by' => $user_id,
                'speaker_id'   => $speaker_id,
                'claim_id'     => $claim_id
            ]
        );
    }

    /**
     * @param int $talk_id
     * @param int $speaker_id
     * @param int $claim_id
     *
     * @return false|int
     */
    public function claimExists($talk_id, $speaker_id, $claim_id)
    {
        $sql      = 'select * from pending_talk_claims WHERE 
                  talk_id = :talk_id AND claim_id = :claim_id AND speaker_id = :speaker_id
                  LIMIT 1
               ';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(
            [
                'talk_id'    => $talk_id,
                'speaker_id' => $speaker_id,
                'claim_id'   => $claim_id
            ]
        );
        if ($response) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($stmt->rowCount() == 0) {
                return false;
            } elseif ($result['user_approved_at'] === null) {
                return self::HOST_ASSIGN;
            } elseif ($result['host_approved_at'] === null) {
                return self::SPEAKER_CLAIM;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * @param int $talk_id
     * @param int $speaker_id
     * @param int $claim_id
     *
     * @return bool
     */
    public function approveAssignmentAsSpeaker($talk_id, $speaker_id, $claim_id)
    {
        $sql  = 'update pending_talk_claims SET user_approved_at = NOW() WHERE 
                  talk_id = :talk_id AND claim_id = :claim_id 
                  AND speaker_id = :speaker_id AND user_approved_at IS NULL
                  LIMIT 1
               ';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            [
                'talk_id'    => $talk_id,
                'speaker_id' => $speaker_id,
                'claim_id'   => $claim_id
            ]
        );
    }

    /**
     * @param int $talk_id
     * @param int $speaker_id
     * @param int $claim_id
     *
     * @return bool
     */
    public function approveClaimAsHost($talk_id, $speaker_id, $claim_id)
    {
        $sql  = 'update pending_talk_claims SET host_approved_at = NOW() WHERE 
                  talk_id = :talk_id AND claim_id = :claim_id 
                  AND speaker_id = :speaker_id AND host_approved_at IS NULL
                  LIMIT 1
               ';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            [
                'talk_id'    => $talk_id,
                'speaker_id' => $speaker_id,
                'claim_id'   => $claim_id
            ]
        );
    }

    /**
     * @param int $talk_id
     * @param int $speaker_id
     * @param int $claim_id
     *
     * @return bool
     */
    public function rejectClaimAsHost($talk_id, $speaker_id, $claim_id)
    {
        $sql  = 'DELETE FROM pending_talk_claims
                  WHERE
                  talk_id = :talk_id AND claim_id = :claim_id
                  AND speaker_id = :speaker_id AND host_approved_at IS NULL
                  LIMIT 1
               ';
        $stmt = $this->_db->prepare($sql);

        return $stmt->execute(
            [
                'talk_id'    => $talk_id,
                'speaker_id' => $speaker_id,
                'claim_id'   => $claim_id
            ]
        );
    }

    /**
     * @param int $event_id
     *
     * @return false|PendingTalkClaimModelCollection
     */
    public function getPendingClaimsByEventId($event_id)
    {
        $base    = $this->_request->base;
        $version = $this->_request->version;

        $sql      = 'select c.*, s.speaker_name from pending_talk_claims c
                    inner join talks t on t.ID = c.talk_id
                    inner join talk_speaker s on s.ID = c.claim_id
                    where t.event_id = :event_id and
                    (host_approved_at IS NULL or user_approved_at IS NULL)';
        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute(['event_id' => $event_id]);

        if (!$response || $stmt->rowCount() < 1) {
            return false;
        }

        $list   = [];
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $date = new DateTime('@' . $row['date_added']);

            $list[] = new PendingTalkClaimModel(
                [
                    'date_added'        => $date->format("c"),
                    'display_name'      => $row['speaker_name'],
                    'talk_uri'          => $base . '/' . $version . '/talks/' . $row['talk_id'],
                    'speaker_uri'       => $base . '/' . $version . '/users/' . $row['speaker_id'],
                    'approve_claim_uri' => $base . '/' . $version . '/talks/' . $row['talk_id'] . '/speakers'
                ]
            );
        }

        return new PendingTalkClaimModelCollection($list, count($list));
    }
}
