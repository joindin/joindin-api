<?php

namespace Joindin\Api\Model;

use Exception;
use PDO;

class LanguageMapper extends ApiMapper
{
    /**
     * @return array
     */
    public function getDefaultFields()
    {
        return [
            'name' => 'lang_name',
            'code' => 'lang_abbr',
        ];
    }

    /**
     * @return array
     */
    public function getVerboseFields()
    {
        return [
            'name' => 'lang_name',
            'code' => 'lang_abbr',
        ];
    }

    /**
     * @param int  $language_id
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getLanguageById($language_id, $verbose = false)
    {
        $results = $this->getLanguages(1, 0, ['ID' => (int)$language_id]);
        if ($results) {
            return $this->transformResults($results, $verbose);
        }

        return false;
    }

    /**
     * @param int  $resultsperpage
     * @param int  $start
     * @param bool $verbose
     *
     * @return false|array
     */
    public function getLanguageList($resultsperpage, $start = 0, $verbose = false)
    {
        $results = $this->getLanguages($resultsperpage, $start);
        if ($results) {
            return $this->transformResults($results, $verbose);
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

        $base    = $this->_request->base;
        $version = $this->_request->version;

        if (is_array($list) && count($list)) {
            foreach ($results as $key => $row) {
                $list[$key]['uri']         = $base . '/' . $version . '/languages/' . $row['ID'];
                $list[$key]['verbose_uri'] = $base . '/' . $version . '/languages/' . $row['ID'] . '?verbose=yes';
            }
        }

        return [
            'languages' => $list,
            'meta'      => $this->getPaginationLinks($list, $total)
        ];
    }

    /**
     * @param int   $resultsperpage
     * @param int   $start
     * @param array $params
     *
     * @return false|array
     */
    protected function getLanguages($resultsperpage, $start, array $params = [])
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

    /**
     * Check whether the given language is known to joindin
     *
     * @param string $language
     *
     * @return boolean
     */
    public function isLanguageValid($language)
    {
        $sql = 'select * from lang where lang_name = :language';

        $stmt = $this->_db->prepare($sql);
        try {
            $stmt->execute([':language' => $language]);
        } catch (Exception $e) {
            return false;
        }

        return count($stmt->fetchAll()) > 0;
    }
}
