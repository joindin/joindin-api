<?php

/**
 * Object that represents a talk
 */
class TalkModel extends AbstractModel
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
            'talk_title'              => 'talk_title',
            'url_friendly_talk_title' => 'url_friendly_talk_title',
            'talk_description'        => 'talk_desc',
            'type'                    => 'talk_type',
            'start_date'              => 'date_given',
            'duration'                => 'duration',
            'stub'                    => 'stub',
            'average_rating'          => 'avg_rating',
            'comments_enabled'        => 'comments_enabled',
            'comment_count'           => 'comment_count',
            'starred'                 => 'starred',
            'starred_count'           => 'starred_count',
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

        $fields['slides_link'] = 'slides_link';
        $fields['language']    = 'lang_name';

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
            'speakers' => 'speakers',
            'tracks'   => 'tracks',
        ];
    }

    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $item = parent::getOutputView($request, $verbose);
        
        // add Hypermedia
        $base    = $request->base;
        $version = $request->version;

        $item['uri']                  = $base . '/' . $version . '/talks/' . $this->ID;
        $item['verbose_uri']          = $base . '/' . $version . '/talks/' . $this->ID . '?verbose=yes';
        $item['website_uri']          = 'http://joind.in/talk/view/' . $this->ID;
        $item['starred_uri']          = $base . '/' . $version . '/talks/' . $this->ID . '/starred';
        $item['comments_uri']         = $base . '/' . $version . '/talks/' . $this->ID . '/comments';
        $item['verbose_comments_uri'] = $base . '/' . $version . '/talks/' . $this->ID
                                        . '/comments?verbose=yes';
        $item['event_uri']            = $base . '/' . $version . '/events/' . $this->event_id;

        return $item;
    }
}
