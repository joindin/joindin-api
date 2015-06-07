<?php

/**
* Object to represent a twitter request token
*/

class TwitterRequestTokenModel {

    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }
    public function __get($field) {
        if(isset($this->data[$field])) {
            return $this->data[$field];
        }

        return false;
    }

    /**
     * The fields to return, with public-facing name first, and database column second
     */
    protected function getDefaultFields() {
        $fields = array(
            'token' => 'token',
        );

        return $fields;
    }

    protected function getVerboseFields() {
        $fields = array(
            'token' => 'token',
            'secret' => 'secret',
        );

        return $fields;
    }


    /**
     * Return this object with client-facing fields and hypermedia, ready for output
     */
    public function getOutputView($request, $verbose = false) {
        $item = array();
        $base = $request->base;
        $version = $request->version;

        if($verbose) {
            $fields = $this->getVerboseFields();
        } else {
            $fields = $this->getDefaultFields();
        }

        foreach($fields as $output_name => $name) {
            $item[$output_name] = $this->$name;
        }

        // what else?  Hypermedia
        $item['uri'] = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID;
        $item['verbose_uri'] = $base . '/' . $version . '/twitter/request_tokens/' . $this->ID . '?verbose=yes';

        return $item;
    }
}
