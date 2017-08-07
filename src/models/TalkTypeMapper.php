<?php

class TalkTypeMapper extends ApiMapper
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

    public function getTalkTypeList($resultsperpage, $start = 0, $verbose = false)
    {
        $results = $this->getTalkTypes($resultsperpage, $start);
        if ($results) {
            return $this->transformResults($results, $verbose);
        }
        return false;
    }

    public function getTalkTypeById($talkTypeId, $verbose)
    {
        $results = $this->getTalkTypes(1, 0, array('ID' => (int) $talkTypeId));
        if ($results) {
            return $this->transformResults($results, $verbose);
        }
        return false;
    }

    protected function getTalkTypes($resultsperpage, $start, $params = array())
    {
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

    /**
     * @inheritdoc
     */
    public function transformResults(array $results, $verbose)
    {
        $total = $results['total'];
        unset($results['total']);

        $list = parent::transformResults($results, $verbose);

        $base = $this->_request->base;
        $version = $this->_request->version;

        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri'] = $base . '/' . $version . '/talk_types/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/talk_types/' . $row['ID'] . '?verbose=yes';
            }
        }

        return array(
            'talk_types' => $list,
            'meta'      => $this->getPaginationLinks($list, $total)
        );
    }

    /**
     * Return a list of title against talk type ID
     *
     * @return array
     */
    public function getTalkTypesLookupList()
    {
        $sql = "select ID, cat_title from categories";
        $stmt = $this->_db->prepare($sql);
        $stmt->execute();

        $list = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lang) {
            $list[$lang['cat_title']] = $lang['ID'];
        }

        return $list;
    }
}
