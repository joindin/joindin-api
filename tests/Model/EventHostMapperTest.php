<?php

namespace Joindin\Api\Test\Model;

use Joindin\Api\Model\EventHostMapper;
use Joindin\Api\Request;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class EventHostMapperTest extends TestCase
{
    public function testThatAddingHostToEventCallsExpectedInsert()
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('execute')
            ->with([
                ':host_id'  => 12,
                ':event_id' => 10,
                ':type'     => 'event'
            ])
            ->willReturn(true);
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->with('INSERT INTO user_admin (uid, rid, rtype) VALUES (:host_id, :event_id, :type)')
            ->willReturn($stmt);
        $pdo->method('lastInsertId')
            ->willReturn(14);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mapper = new EventHostMapper($pdo, $request);

        $this->assertEquals(14, $mapper->addHostToEvent(10, 12));
    }

    public function testThatFailingExecuteResultsInFalseBeingReturned()
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('execute')
            ->with([
                ':host_id'  => 12,
                ':event_id' => 10,
                ':type'     => 'event'
            ])
            ->willReturn(false);
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->with('INSERT INTO user_admin (uid, rid, rtype) VALUES (:host_id, :event_id, :type)')
            ->willReturn($stmt);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mapper = new EventHostMapper($pdo, $request);

        $this->assertFalse($mapper->addHostToEvent(10, 12));
    }

    public function testThatGettingHostByEventIdReturnsSomethingSensibleWhenStatementReturnsFalse()
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('execute')
            ->with([
                ':event_id' => 12,
            ])
            ->willReturn(false);
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->with('select a.uid as user_id, u.full_name as host_name from user_admin a inner join user u on u.ID = a.uid where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null) order by host_name  LIMIT 0,10')
            ->willReturn($stmt);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mapper = new EventHostMapper($pdo, $request);

        $this->assertFalse($mapper->getHostsByEventId(12, 10, 0));
    }

    public function testThatGettingHostByEventIdReturnsSomethingSensibleWhenStatementReturnsSingleValue()
    {
        $stmt1 = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt1->method('execute')
            ->with([
                ':event_id' => 12,
            ])
            ->willReturn(true);
        $stmt1->method('fetchAll')
            ->willReturn([
                ['user_id' => 8, 'host_name' => 'Karl Napp'],
            ]);
        $stmt2 = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt2->method('execute')
            ->with([
                ':event_id' => 12,
            ])
            ->willReturn(true);
        $stmt2->method('fetchAll')
            ->willReturn(1);
        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $pdo->expects($this->at(0))
            ->method('prepare')
            ->with('select a.uid as user_id, u.full_name as host_name from user_admin a inner join user u on u.ID = a.uid where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null) order by host_name  LIMIT 0,10')
            ->willReturn($stmt1);
        $pdo->expects($this->at(1))
            ->method('prepare')
            ->with('SELECT count(*) AS count FROM (select a.uid as user_id, u.full_name as host_name from user_admin a inner join user u on u.ID = a.uid where rid = :event_id and rtype="event" and (rcode!="pending" OR rcode is null) order by host_name  ) as counter')
            ->willReturn($stmt2);
        $request                       = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->base                 = 'test';
        $request->version              = '1';
        $request->path_info            = '2';
        $request->paginationParameters = ['resultsperpage' => 10, 'start' => 0];

        $mapper = new EventHostMapper($pdo, $request);

        $this->assertEquals([
            'hosts' => [
                [
                    'host_name' => 'Karl Napp',
                    'host_uri'  => 'test/1/users/8',
                ]
            ],
            'meta'  => [
                'count'     => 1,
                'total'     => null,
                'this_page' => 'test2?resultsperpage=10&start=0'
            ]
        ], $mapper->getHostsByEventId(12, 10, 0));
    }

    public function testThatRemovingAHostFromAnEventCallsTheExpectedMethods()
    {
        $stmt1 = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt1->method('execute')
            ->with([
                ':user_id'  => 12,
                ':event_id' => 14,
                ':type'     => 'event',
            ])
            ->willReturn(true);

        $pdo = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $pdo->expects($this->once())
            ->method('prepare')
            ->with('DELETE FROM user_admin WHERE uid = :user_id AND rid = :event_id AND rtype = :type')
            ->willReturn($stmt1);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mapper = new EventHostMapper($pdo, $request);
        $this->assertTrue($mapper->removeHostFromEvent(12, 14));
    }

    public function testGetVerboseFields()
    {
        $eventHostMapper = new EventHostMapper($this->createMock(\PDO::class));

        $this->assertEquals(
            [
                'host_name' => 'host_name',
                'host_uri'  => 'host_uri',
            ],
            $eventHostMapper->getVerboseFields()
        );
    }
}
