<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Container for multiple TwitterRequestTokenModel objects, also handles
 * collection metadata such as pagination
 */
class TwitterRequestTokenModelCollection extends BaseModelCollection
{
    /** @var array|TwitterRequestTokenModel[] */
    protected array $list = [];

    /**
     * Take arrays of data and create a collection of models; store metadata
     *
     * @param array $data
     * @param int   $total
     */
    public function __construct(array $data, int $total = 0)
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
     *
     * @param Request $request
     * @param bool    $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
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
