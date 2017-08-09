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
}
