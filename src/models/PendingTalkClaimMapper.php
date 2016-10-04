<?php

/**
 * PendingTalkClaimMapper
 *
 * @uses ApiModel
 * @package API
 */
class PendingTalkClaimMapper extends ApiMapper
{

    /**
     * Default mapping for column names to API field names
     *
     * @return array with keys as API fields and values as db columns
     */
    public function getDefaultFields()
    {
        $fields = array(
            "talk_id"           => "username",
            "speaker_id"        => "full_name",
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
            "talk_id"           => "username",
            "speaker_id"        => "full_name",
            "date_added"        => "date_added",
            "claim_id"          => "claim_id",
            "user_approved_at"  => "user_approved_at",
            "host_approved_at"  => "host_approved_at",
        );

        return $fields;
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
            array(
                'talk_id'       => $talk_id,
                'submitted_by'  => $speaker_id,
                'speaker_id'    => $speaker_id,
                'claim_id'      => $claim_id
            )
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
            array(
                'talk_id'       => $talk_id,
                'submitted_by'  => $user_id,
                'speaker_id'    => $speaker_id,
                'claim_id'      => $claim_id
            )
        );
    }
}
