<?php

/**
 * Container for multiple TwitterRequestTokenModel objects, also handles
 * collection metadata such as pagination
 */
class TwitterRequestTokenModelCollection extends AbstractModelCollection
{
    /**
     * Take arrays of data and create a collection of models; store metadata
     */
    public function __construct(array $data, $total = 0)
    {
        $this->total = $total;

        // hydrate the model objects
        foreach ($data as $item) {
            $this->list[] = new TwitterRequestTokenModel($item);
        }
    }

    /**
     * Present this collection ready for the output handlers
     *
     * This creates the expected output structure, converting each resource
     * to it's presentable representation and adding the meta fields for totals
     * and pagination
     */
    public function getOutputView($request, $verbose = false)
    {
        // handle the collection first
        $retval = ['twitter_request_tokens' => []];
        foreach ($this->list as $item) {
            $retval['twitter_request_tokens'][] = $item->getOutputView($request, $verbose);
        }

        // add other fields
        $retval['meta'] = $this->addPaginationLinks($request);

        return $retval;
    }
}
