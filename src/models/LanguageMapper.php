<?php

class LanguageMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        $fields = array(
            'name' => 'lang_name',
            'code' => 'lang_abbr',
        );

        return $fields;
    }

    public function getVerboseFields()
    {
        $fields = array(
            'name' => 'lang_name',
            'code' => 'lang_abbr',
        );

        return $fields;
    }

    public function getLanguageById($language_id, $verbose = false)
    {
        $results = $this->getLanguages(1, 0, array('ID' => (int) $language_id));
        if ($results) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    public function getLanguageList($resultsperpage, $start = 0, $verbose = false)
    {
        $results = $this->getLanguages($resultsperpage, $start);
        if ($results) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    public function transformResults($results, $verbose)
    {
        $total = $results['total'];
        unset($results['total']);

        $list = parent::transformResults($results, $verbose);

        $base    = $this->_request->base;
        $version = $this->_request->version;

        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri']         = $base . '/' . $version . '/languages/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/languages/' . $row['ID'] . '?verbose=yes';
            }
        }

        return array(
            'languages' => $list,
            'meta'      => $this->getPaginationLinks($list, $total)
        );
    }

    protected function getLanguages($resultsperpage, $start, $params = array())
    {
        $sql = 'select l.ID, l.lang_name, l.lang_abbr ' .
               'from lang as l ';

        if (count($params) > 0) {
            $value = reset($params);
            $key   = key($params);

            $sql .= 'where l.' . $key . ' = :' . $key . ' ';

            if (count($params) > 1) {
                foreach ($params as $key => $value) {
                    $sql .= 'and l.' . $key . ' = :' . $key . ' ';
                }
            }
        }

        $sql .= 'order by l.lang_name ';
        $sql .= $this->buildLimit($resultsperpage, $start);

        $stmt     = $this->_db->prepare($sql);
        $response = $stmt->execute($params);
        if ($response) {
            $results          = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results['total'] = $this->getTotalCount($sql, $params);

            return $results;
        }

        return false;
    }
}
