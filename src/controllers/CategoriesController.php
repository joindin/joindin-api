<?php
class CategoriesController extends ApiController
{
    public function getAllCategories($request, $db)
    {
        // verbosity - here for consistency as we don't have verbose category details to return at the moment
        $verbose = $this->getVerbosity($request);

        // pagination settings
        $start = $this->getStart($request);
        $resultsperpage = $this->getResultsPerPage($request);

        $mapper = new CategoryMapper($db, $request);
        $list = $mapper->getCategoryList($resultsperpage, $start, $verbose);
        return $list;
    }

    public function getCategory($request, $db)
    {
        $category_id = $this->getItemId($request);
        // verbosity - here for consistency as we don't have verbose category details to return at the moment
        $verbose = $this->getVerbosity($request);

        $mapper = new CategoryMapper($db, $request);
        $list = $mapper->getCategoryById($category_id, $verbose);

        if (count($list['categories']) == 0) {
            throw new Exception('Category not found', 404);
        }
        return $list;
    }
}
