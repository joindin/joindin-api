<?php

class TestApiMapper extends ApiMapper
{

    public function getDefaultFields()
    {
        return [
            'event_tz_place' => 'event_tz_place',
            'event_tz_cont' => 'event_tz_cont',
            'event_start_date' => 'event_start_date',
            'name' => 'name',
        ];
    }

    public function getVerboseFields()
    {
        return [];
    }

    // this is here to allow testing of a protected method
    public function buildLimit($resultsperpage, $start)
    {
        return parent::buildLimit(
            $resultsperpage,
            $start
        );
    }

    // this is here to allow testing of a protected method
    public function inflect($string)
    {
        return parent::inflect($string);
    }
}
