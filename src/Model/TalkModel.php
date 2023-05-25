<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Object that represents a talk
 *
 * @property int $ID
 * @property int $event_id
 * @property array $speakers
 * @property string $stub
 * @property string $talk_title
 */
#[\AllowDynamicProperties]
class TalkModel extends BaseModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    public function getDefaultFields(): array
    {
        return [
            'id'                      => 'ID',
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

        $fields['slides_link'] = 'slides_link';
        $fields['talk_media']  = 'talk_media';
        $fields['language']    = 'lang_name';
        $fields['user_rating'] = 'user_rating';

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

        $item['uri']                  = $base . '/' . $version . '/talks/' . $this->ID;
        $item['verbose_uri']          = $base . '/' . $version . '/talks/' . $this->ID . '?verbose=yes';
        $item['website_uri']          = $this->getWebsiteUrl($request->getConfigValue('website_url'));
        $item['starred_uri']          = $base . '/' . $version . '/talks/' . $this->ID . '/starred';
        $item['tracks_uri']           = $base . '/' . $version . '/talks/' . $this->ID . '/tracks';
        $item['comments_uri']         = $base . '/' . $version . '/talks/' . $this->ID . '/comments';
        $item['verbose_comments_uri'] = $base . '/' . $version . '/talks/' . $this->ID
                                        . '/comments?verbose=yes';
        $item['event_uri']            = $base . '/' . $version . '/events/' . $this->event_id;
        $item['speakers_uri']         = $base . '/' . $version . '/talks/' . $this->ID . '/speakers';

        return $item;
    }

    /**
     * Get the URL on the website of this talk
     *
     * @param string $website_url The URL to the main website (e.g. http://joind.in or http://test.joind.in)
     *
     * @return string The link to the talk on the web (e.g. http://web2.dev.joind.in/talk/ed89b)
     **/
    public function getWebsiteUrl($website_url)
    {
        return $website_url . "/talk/" . $this->stub;
    }
}
