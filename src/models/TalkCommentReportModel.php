<?php

/**
 * Object that represents a reported event comment
 */
class TalkCommentReportModel extends AbstractModel
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
            'reporting_date'          => 'reporting_date',
            'decision'                => 'decision',
            'deciding_date'           => 'deciding_date',
            'reporting_user_username' => 'reporting_username',
            'deciding_user_username'  => 'deciding_username',
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

        return $fields;
    }

    /**
     * List of subresource keys that may be in the data set from the mapper
     * but are not database columns that need to be in the output view
     *
     * format: [public facing name => field in $this->data]
     *
     * @return array
     */
    public function getSubResources()
    {
        return [
            'comment'   => 'comment',
        ];
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     *
     * @param Request $request
     * @param bool $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);
        
        // add Hypermedia
        $base    = $request->base;
        $version = $request->version;

        $item['reporting_user_uri'] = $base . '/' . $version . '/users/' . $this->reporting_user_id;
        if (!empty($this->deciding_user_id)) {
            $item['deciding_user_uri']        = $base . '/' . $version . '/users/' . $this->deciding_user_id;
        }
        $item['event_uri']            = $base . '/' . $version . '/events/' . $this->event_id;
        $item['talk_uri']             = $base . '/' . $version . '/talks/' . $this->talk_id;

        return $item;
    }
}
