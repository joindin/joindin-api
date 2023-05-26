<?php

namespace Joindin\Api\Model;

use Joindin\Api\Request;

/**
 * Container for multiple EventCommentReportModel objects
 */
class TalkCommentReportModelCollection extends BaseModelCollection
{
    /** @var array|TalkCommentReportModel[] */
    protected $list;

    protected int $total;

    /**
     * Take arrays of data and create a collection of models; store metadata
     *
     * @param array $data
     * @param int   $total
     */
    public function __construct(array $data, $total)
    {
        $this->list  = [];
        $this->total = $total;

        // hydrate the model objects if necessary and store to list
        foreach ($data as $item) {
            if (!$item instanceof TalkCommentReportModel) {
                $item = new TalkCommentReportModel($item);
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
        // handle the collection first
        $retval = ['reports' => []];

        foreach ($this->list as $item) {
            $retval['reports'][] = $item->getOutputView($request, $verbose);
        }

        // add other fields
        $retval['meta'] = $this->addPaginationLinks($request);

        return $retval;
    }
}
