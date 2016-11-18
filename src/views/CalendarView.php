<?php

/**
 * HTML View class: renders HTML 5
 *
 * @category View
 * @package  API
 * @author   Lorna Mitchel <lorna.mitchell@gmail.com>
 * @author   Rob Allen <rob@akrabat.com>
 * @license  BSD see doc/LICENSE
 */
class CalendarView extends ApiView
{

    public function render($content)
    {
        $this->setHeader('Content-Type', 'text/calendar; charset=utf8');

        return parent::render($content);
    }
    /**
     * Render the view
     *
     * @param array $content data to be rendered
     *
     * @return string
     */
    public function buildOutput($content)
    {
        $vcalendar = new \Sabre\VObject\Component\VCalendar();

        if (! isset($content['events'])) {
            return $vcalendar->serialize();
        }

        if (! is_array($content['events'])) {
            return $vcalendar->serialize();
        }

        foreach ($content['events'] as $event) {
            $event = $this->createEvent($event, $vcalendar);
            if (! $event instanceof \Sabre\VObject\Component\VEvent) {
                continue;
            }

            $vcalendar->add($event);
        }

        return $vcalendar->serialize();
    }

    /**
     * Create an Event from an array entry
     *
     * @param array $content data to be rendered
     *
     * @return
     */
    private function createEvent(array $content, $vcalendar)
    {
        $event = new \Sabre\VObject\Component\VEvent($vcalendar, 'VEVENT');
        $event->add('SUMMARY', $content['name']);
        $event->add('DTSTART', (new \DateTime($content['start_date']))->setTimezone(new \DateTimeZone($content['tz_continent'] . '/' . $content['tz_place'])));
        $event->add('DTEND', (new \DateTime($content['end_date']))->setTimezone(new \DateTimeZone($content['tz_continent'] . '/' . $content['tz_place'])));
        $event->add('DESCRIPTION', $content['description']);
        $event->add('URI', $content['href']);
        $event->add('LOCATION', $content['location']);
        return $event;
    }
}
