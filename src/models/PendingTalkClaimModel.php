<?php

/**
 * Object that represents a talk
 */
class PendingTalkClaimModel extends AbstractModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getDefaultFields()
    {
        $fields = array(
            'date_added'              => 'date_added',
            'display_name'            => 'display_name',
            'speaker_uri'             => 'speaker_uri',
            'talk_uri'                => 'talk_uri',
        );

        return $fields;
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getVerboseFields()
    {
        $fields = $this->getDefaultFields();

        $fields['approve_claim_uri'] = 'approve_claim_uri';

        return $fields;
    }


    /**
     * Return an array with client-facing fields and hypermedia, ready for output
     *
     * @param Request $request
     * @param bool $verbose
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);
        
        // add Hypermedia
        $base    = $request->base;
        $version = $request->version;

        return $item;
    }
}
