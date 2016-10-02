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

    public function claimTalkAsSpeaker($talk_id){
        $sql  = 'insert into pending_talk_claims 
                    (talk_id,submitted_by,speaker_id,date_added,claim_id,user_approved_at) 
                  values
                    (:talk_id,:submitted_by,:speaker_id,NOW(),:claim_id,NOW())
                 ';
        $stmt = $this->_db->prepare($sql);
        $stmt->execute(array('uid' => $user_id, 'tid' => $talk_id));

        return true;
    }


}
