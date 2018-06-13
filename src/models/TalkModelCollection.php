<?php

/**
 * Container for multiple TalkModel objects, also handles
 * collection metadata such as pagination
 */
class TalkModelCollection extends BaseModelCollection
{
    /** @var array */
    protected $list;

    /** @var int */
    protected $total;

    private $modifier;

    /**
     * Take arrays of data and create a collection of models; store metadata
     *
     * @param array $data
     * @param int $total
     */
    public function __construct(array $data, $total)
    {
        $this->list = [];
        $this->total = $total;
        $this->modifier = new \Joindin\Modifier\NullModifier();

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
     *
     * @param Request $request
     * @param bool $verbose
     *
     * @return array
     */
    public function getOutputView(Request $request, $verbose = false)
    {
        // handle the collection first
        $retval= ['talks' => []];
        foreach ($this->list as $item) {
            $item = $this->modifier->modify($item);
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

    public function setModifier(\Joindin\Modifier\TalkModelModifying $modifier)
    {
        $this->modifier = $modifier;
    }
}
