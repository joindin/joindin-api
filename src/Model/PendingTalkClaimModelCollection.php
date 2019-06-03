<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Container for multiple EventCommentReportModel objects
 */
class PendingTalkClaimModelCollection extends BaseModelCollection
{
    /** @var array|PendingTalkClaimModel[] */
    protected $list;

    /** @var int */
    protected $total;

    /**
     * Take arrays of data and create a collection of models; store metadata
     *
     * @param array $data
     * @param       $total
     */
    public function __construct(array $data, $total)
    {
        $this->list  = [];
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
     *
     * @param Request $request
     * @param bool    $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        $retval = [];
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
