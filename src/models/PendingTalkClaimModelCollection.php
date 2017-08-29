<?php

/**
 * Container for multiple EventCommentReportModel objects
 */
class PendingTalkClaimModelCollection extends AbstractModelCollection
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
            if (!$item instanceof PendingTalkClaimModel) {
                $item = new PendingTalkClaimModel($item);
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
        $retval = ['claims' => []];
        foreach ($this->list as $item) {
            $retval['claims'][] = $item->getOutputView($request, $verbose);
        }

        // add other fields
        $retval['meta'] = $this->addPaginationLinks($request);

        return $retval;
    }
}
