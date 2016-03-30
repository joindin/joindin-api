<?php

class EventMapperTest extends PHPUnit_Framework_TestCase
{
    public function testCannotCreateIfEndDateIsBeforeStartDate()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Start Date must be before End Date");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $start = "2014-09-08T12:15:00+01:00";
        $end = "2014-07-08T12:15:00+01:00";

        $eventDataArray = $this->buildValidEventData();
        $eventDataArray['start_date'] = $start;
        $eventDataArray['end_date'] = $end;

        $mapper->createEvent($eventDataArray);
    }

    public function testStartDateIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['start_date']);

        $mapper->createEvent($eventDataArray);
    }

    public function testEndDateIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['end_date']);

        $mapper->createEvent($eventDataArray);
    }

    public function testNameIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['name']);

        $mapper->createEvent($eventDataArray);
    }

    public function testDescriptionIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['description']);

        $mapper->createEvent($eventDataArray);
    }

    public function testTZContinentIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['tz_continent']);

        $mapper->createEvent($eventDataArray);
    }

    public function testTZPlaceIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['tz_place']);

        $mapper->createEvent($eventDataArray);
    }

    public function testContactNameIsRequired()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Missing mandatory fields");

        $pdo = $this->getPDOMock();

        $mapper = new \EventMapper($pdo);

        $eventDataArray = $this->buildValidEventData();
        unset($eventDataArray['contact_name']);

        $mapper->createEvent($eventDataArray);
    }

    private function getPDOMock()
    {
        return $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function buildValidEventData()
    {
        return [
            'name' => "AmazingConference PHP",
            'description' => "It's an amazing conference about PHP",
            'start_date' => '2014-09-08T12:15:00+01:00',
            'end_date' => '2014-09-10T12:15:00+01:00',
            'tz_continent' => 'A Continent',
            'tz_place' => 'A Place',
            'contact_name' => "John Doe",
        ];;
    }
}
