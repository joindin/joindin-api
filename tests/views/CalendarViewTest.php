<?php

/**
 * @covers CalendarView
 */
class CalendarViewTest extends PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testBuildOutput
     *
     * @return array
     */
    public function buildOutputProvider()
    {
        $default = 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 3.5.3//EN
CALSCALE:GREGORIAN
END:VCALENDAR
';

        return [
            0 => [
                'input' => ['a' => 'b', 'c' => 10],
                'expected' => $default,
            ],
            1 => [
                'input' => ['events' => '10', 'b' => ['c', 'd']],
                'expected' => $default
            ],
            2 => [
                'input' => false,
                'expected' => $default
            ],
            3 => [
                'input' => [
                    'events' => [[
                        'name' => 'name',
                        'tz_continent' => 'Europe',
                        'tz_place' => 'Paris',
                        'start_date' => '2017-12-13 12:00:00',
                        'end_date'   => '2017-12-14 14:00:00',
                        'description' => 'description',
                        'href' => 'href',
                        'location' => 'location',
                    ]]
                ],
                'expected' => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Sabre//Sabre VObject 3.5.3//EN
CALSCALE:GREGORIAN
BEGIN:VEVENT
UID:d24c79c8aea6013bfe9bcbbf486431661568426d
DTSTAMP:%datetime%
SUMMARY:name
DTSTART;TZID=Europe/Paris:20171213T130000
DTEND;TZID=Europe/Paris:20171214T150000
DESCRIPTION:description
URI:href
LOCATION:location
END:VEVENT
END:VCALENDAR
'
            ]
        ];
    }

    /**
     * @dataProvider buildOutputProvider
     *
     * @covers JsonView::buildOutput
     *
     * @param mixed $input
     * @param string $expected
     */
    public function testBuildOutput($input, $expected)
    {
        $view = new CalendarView();
        $datetime = (new DateTime())
            ->setTimezone(new dateTimezone('UTC'))
            ->format('Ymd\THis\Z');
        $this->assertEquals(
            str_replace('%datetime%', $datetime, $expected),
            $view->buildOutput($input)
        );
    }
}
