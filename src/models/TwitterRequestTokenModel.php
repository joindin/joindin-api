<?php

/**
 * Object to represent a twitter request token
 */
class TwitterRequestTokenModel extends AbstractModel
{
    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    protected function getDefaultFields()
    {
        return array(
            'token' => 'token',
        );
    }

    /**
     * Default fields in the output view
     *
     * format: [public facing name => database column]
     *
     * @return array
     */
    protected function getVerboseFields()
    {
        return array(
            'token'  => 'token',
            'secret' => 'secret',
        );
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

        $item['uri']         = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID;
        $item['verbose_uri'] = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID . '?verbose=yes';

        return $item;
    }
}
