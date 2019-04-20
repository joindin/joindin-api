<?php

class TestApiMapper extends ApiMapper
{
    public function getDefaultFields()
    {
        return [
            'event_tz_place'   => 'event_tz_place',
            'event_tz_cont'    => 'event_tz_cont',
            'event_start_date' => 'event_start_date',
            'name'             => 'name',
        ];
    }

    public function getVerboseFields()
    {
        return [];
    }

    /**
     * @param int $resultsperpage
     * @param int $start
     *
     * @return string
     */
    public function buildLimit($resultsperpage, $start)
    {
        return parent::buildLimit(
            $resultsperpage,
            $start
        ); // TODO: Change the autogenerated stub
    }

    public function getPaginationLinks(array $list, $total = 0)
    {
        return parent::getPaginationLinks(
            $list,
            $total
        ); // TODO: Change the autogenerated stub
    }

    public function inflect($string)
    {
        return parent::inflect($string); // TODO: Change the autogenerated stub
    }
}
