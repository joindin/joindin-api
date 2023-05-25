<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Object that represents a talk
 */
class PendingTalkClaimModel extends BaseModel
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
        return [
            'date_added'   => 'date_added',
            'display_name' => 'display_name',
            'speaker_uri'  => 'speaker_uri',
            'talk_uri'     => 'talk_uri',
        ];
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
     *
     * @return array
     */
    public function getOutputView(Request $request, bool $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);

        // add Hypermedia
        $base    = $request->base;
        $version = $request->version;

        return $item;
    }
}
