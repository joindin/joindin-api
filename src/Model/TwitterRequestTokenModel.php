<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Object to represent a twitter request token
 *
 * @property int $ID
 */
class TwitterRequestTokenModel extends BaseModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    protected function getDefaultFields(): array
    {
        return [
            'token' => 'token',
        ];
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    protected function getVerboseFields(): array
    {
        return [
            'token'  => 'token',
            'secret' => 'secret',
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
    public function getOutputView(Request $request, bool $verbose = false): array
    {
        $item = parent::getOutputView($request, $verbose);

        // add Hypermedia
        $base    = $request->base;
        $version = $request->version;

        $item['uri']         = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID;
        $item['verbose_uri'] = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID . '?verbose=yes';

        return $item;
    }
}
