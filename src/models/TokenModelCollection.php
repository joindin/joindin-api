<?php

/**
 * Container for multiple Token objects, also handles
 * collection metadata such as pagination
 */
class TokenModelCollection extends AbstractModelCollection
{
    protected $list = array();
    protected $total;

    /**
     * Take arrays of data and create a collection of models; store metadata
     */
    public function __construct(array $data, $total)
    {
        $this->total = $total;

        // hydrate the model objects if necessary and store to list
        foreach ($data as $item) {
            if (!$item instanceof TokenModel) {
                $item = new TokenModel($item);
            }
            $this->list[] = $item;
        }
    }

    /**
     * Present this collection ready for the output handlers
     *
     * This creates the expected output structure, converting each resource
     * to it's presentable representation and adding the meta fields for totals
     * and pagination
     *
     * @
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        // handle the collection first
        $retval['tokens'] = [];
        foreach ($this->list as $item) {
            $retval['tokens'][] = $item->getOutputView($request, $verbose);
        }

        // add other fields
        $retval['meta'] = $this->addPaginationLinks($request);

        return $retval;
    }

    /**
     * Return the list of talks (internal representation)
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->list;
    }
}
