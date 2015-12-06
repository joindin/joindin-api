<?php

/**
 * Container for multiple TalkModel objects, also handles
 * collection metadata such as pagination
 */
class TalkModelCollection extends AbstractModelCollection
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
            if (!$item instanceof TalkModel) {
                $item = new TalkModel($item);
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
     */
    public function getOutputView($request, $verbose = false)
    {
        // handle the collection first
        $retval['talks'] = [];
        foreach ($this->list as $item) {
            $retval['talks'][] = $item->getOutputView($request, $verbose);
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
    public function getTalks()
    {
        return $this->list;
    }
 
    /**
     * Return a single talk (internal representation)
     *
     * @return TalkModel|false
     */
    public function getTalk($index)
    {
        if (!isset($this->list[$index])) {
            return false;
        }

        return $this->list[$index];
    }
}
