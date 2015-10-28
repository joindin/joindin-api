<?php

class CategoryMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        $fields = array(
            'title' => 'cat_title',
            'description' => 'cat_desc',
        );

        return $fields;
    }

    public function getVerboseFields()
    {
        $fields = array(
            'title' => 'cat_title',
            'description' => 'cat_desc',
        );

        return $fields;
    }

    public function getCategoryList($resultsperpage, $start = 0, $verbose = false)
    {
        $results = $this->getCategories($resultsperpage, $start);
        if ($results) {
            return $this->transformResults($results, $verbose);
        }
        return false;
    }

    public function getCategoryById($categoryId, $verbose)
    {
        $results = $this->getCategories(1, 0, array('ID' => (int) $categoryId));
        if ($results) {
            return $this->transformResults($results, $verbose);
        }
        return false;
    }

    protected function getCategories($resultsperpage, $start, $params = array()) {
        $sql = 'select c.ID, c.cat_title, c.cat_desc ' .
               'from categories as c ';

        if (count($params) > 0) {
            $value = reset($params);
            $key = key($params);

            $sql .= 'where c.' . $key . ' = :' . $key . ' ';

            if (count($params) > 1) {
                foreach ($params as $key => $value) {
                    $sql .= 'and c.' . $key . ' = :' . $key . ' ';
                }
            }
        }

        $sql .= 'order by c.ID ';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt = $this->_db->prepare($sql);
        $response = $stmt->execute($params);
        if ($response) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $params);

            return $results;
        }
        return false;
    }

    public function transformResults($results, $verbose)
    {
        $total = $results['total'];
        unset($results['total']);

        $list = parent::transformResults($results, $verbose);

        $base = $this->_request->base;
        $version = $this->_request->version;

        if(is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri'] = $base . '/' . $version . '/categories/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/categories/' . $row['ID'] . '?verbose=yes';
            }
        }

        return array(
            'categories' => $list,
            'meta'      => $this->getPaginationLinks($list, $total)
        );
    }
}
