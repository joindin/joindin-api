<?php

namespace Joindin\Api\Test\Controller;

use PHPUnit\Framework\TestCase;

class EventHostsControllerTest extends TestCase
{
    public function testThatNotLoggedInUsersCanNotAddAHost()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in to create data');
        $this->expectExceptionCode(401);

        $controller = new \Joindin\Api\Controller\EventHostsController();

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $db      = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatRemovingHostWithoutLoginFails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You must be logged in to remove data');
        $this->expectExceptionCode(401);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = null;

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatMissingEventThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found');
        $this->expectExceptionCode(404);

        $controller = new \Joindin\Api\Controller\EventHostsController();

        $em = $this->getMockBuilder('Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(false);

        $controller->setEventMapper($em);

        $request               = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id      = 2;

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatRemovingOneselfThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You are not allowed to remove yourself from the host-list');
        $this->expectExceptionCode(403);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [5 => 1];

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatInvalidEventThrowsException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Event not found');
        $this->expectExceptionCode(404);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [
            3 => 4,
            5 => 2,
        ];

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $eventMapper = $this->getMockBuilder('\Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $eventMapper->method('getEventById')->willReturn(false);

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->setEventMapper($eventMapper);

        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatExceptionIsThrownWhenNonAdminUserTriesToAddHostToEvent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to add hosts to this event');
        $this->expectExceptionCode(403);

        $controller = new \Joindin\Api\Controller\EventHostsController();

        $em = $this->getMockBuilder('Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(false);

        $controller->setEventMapper($em);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id = 2;

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatUserThatIsNotAdminOnEventWillThrowException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to remove hosts from this event');
        $this->expectExceptionCode(403);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [
            3 => 4,
            5 => 2,
        ];

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $eventMapper = $this->getMockBuilder('\Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $eventMapper->method('getEventById')->willReturn(true);
        $eventMapper->method('thisUserHasAdminOn')->willReturn(false);

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->setEventMapper($eventMapper);

        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatExceptionIsThrownWhenUnknownUserShallBeAddedAsHostToEvent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No User found');
        $this->expectExceptionCode(404);

        $controller = new \Joindin\Api\ControllerEventHostsController();

        $em = $this->getMockBuilder('Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(false);

        $controller->setUserMapper($um);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id = 2;
        $request->method('getParameter')->willReturn('myhostname');

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatSettingUnknownUserWillThrowException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No User found');
        $this->expectExceptionCode(404);

        $request = $this->getMockBuilder(Joindin\Api\Request::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [
            3 => 4,
            5 => 2,
        ];

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $eventMapper = $this->getMockBuilder('\Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $eventMapper->method('getEventById')->willReturn(true);
        $eventMapper->method('thisUserHasAdminOn')->willReturn(true);

        $userMapper = $this->getMockBuilder('\Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $userMapper->method('getUserById')->willReturn(false);

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->setEventMapper($eventMapper);
        $constructor->setUserMapper($userMapper);

        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatExceptionIsThrownWhenEventHostMapperHasProblems()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong');
        $this->expectExceptionCode(400);

        $controller = new \Joindin\Api\Controller\EventHostsController();

        $em = $this->getMockBuilder('Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(13);

        $controller->setUserMapper($um);

        $ehm = $this->getMockBuilder('Joindin\Api\Model\EventHostMapper')->disableOriginalConstructor()->getMock();
        $ehm->expects($this->once())->method('addHostToEvent')->with($this->equalTo(12, 13))->willReturn(false);

        $controller->setEventHostMapper($ehm);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 12];
        $request->user_id = 2;
        $request->method('getParameter')->willReturn('myhostname');

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatViewGetsCorrectValuesWhenEverythingWorksAsExpected()
    {
        $controller = new \Joindin\Api\Controller\EventHostsController();

        $em = $this->getMockBuilder('Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(13);

        $controller->setUserMapper($um);

        $ehm = $this->getMockBuilder('Joindin\Api\Model\EventHostMapper')->disableOriginalConstructor()->getMock();
        $ehm->expects($this->once())->method('addHostToEvent')->with($this->equalTo(12), $this->equalTo(13))->willReturn(true);

        $controller->setEventHostMapper($ehm);

        $view = $this->getMockBuilder('\Joindin\Api\View\ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with(
            $this->equalTo('Location'),
            $this->equalTo('foo//events/12/hosts')
        );
        $view->expects($this->once())->method('setResponseCode')->with($this->equalTo(201));
        $view->expects($this->once())->method('setNoRender')->with($this->equalTo(true));

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 12];
        $request->user_id = 2;
        $request->base = 'foo';
        $request->method('getParameter')->willReturn('myhostname');
        $request->method('getView')->willReturn($view);

        $controller->addHost($request, $db);
    }

    public function testThatFailureWhileRemovingUserAsHostWillThrowException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Something went wrong');
        $this->expectExceptionCode(400);

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [
            3 => 4,
            5 => 2,
        ];

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $eventMapper = $this->getMockBuilder('\Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $eventMapper->method('getEventById')->willReturn(true);
        $eventMapper->method('thisUserHasAdminOn')->willReturn(true);

        $userMapper = $this->getMockBuilder('\Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $userMapper->method('getUserById')->willReturn(true);

        $eventHostMapper = $this->getMockBuilder('\Joindin\Api\Model\EventHostMapper')->disableOriginalConstructor()->getMock();
        $eventHostMapper->method('removeHostFromEvent')->willReturn(false);

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->setEventMapper($eventMapper);
        $constructor->setUserMapper($userMapper);
        $constructor->setEventHostMapper($eventHostMapper);

        $constructor->removeHostFromEvent($request, $db);
    }

    public function testThatRemovingUserAsHostSetsCorrectValues()
    {
        $view = $this->getMockBuilder('\Joindin\Api\View\ApiView')->getMock();
        $view->method('setHeader')->with('Location', 'base/version/events/4/hosts');
        $view->method('setResponseCode')->with(204);
        $view->method('setNoRender')->with(true);

        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();
        $request->user_id = 1;
        $request->url_elements = [
            3 => 4,
            5 => 2,
        ];
        $request->base = 'base';
        $request->version = 'version';
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $eventMapper = $this->getMockBuilder('\Joindin\Api\Model\EventMapper')->disableOriginalConstructor()->getMock();
        $eventMapper->method('getEventById')->willReturn(true);
        $eventMapper->method('thisUserHasAdminOn')->willReturn(true);

        $userMapper = $this->getMockBuilder('\Joindin\Api\Model\UserMapper')->disableOriginalConstructor()->getMock();
        $userMapper->method('getUserById')->willReturn(true);

        $eventHostMapper = $this->getMockBuilder('\Joindin\Api\Model\EventHostMapper')->disableOriginalConstructor()->getMock();
        $eventHostMapper->method('removeHostFromEvent')->willReturn(true);

        $constructor = new \Joindin\Api\Controller\EventHostsController();
        $constructor->setEventMapper($eventMapper);
        $constructor->setUserMapper($userMapper);
        $constructor->setEventHostMapper($eventHostMapper);

        $this->assertNull($constructor->removeHostFromEvent($request, $db));
    }

    public function testThatGettingEventHostWapperMithoutSettingFirstWorksAsExpected()
    {
        $controller = new \Joindin\Api\Controller\EventHostsController();

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();

        $automatedEventHostMapper = $controller->getEventHostMapper($request, $db);

        $this->assertInstanceOf(Joindin\Api\Model\EventHostMapper::class, $automatedEventHostMapper);
        $this->assertSame($automatedEventHostMapper, $controller->getEventHostMapper($request, $db));
    }

    public function testThatGettingUserMapperWithoutSettingFirstWorksAsExpected()
    {
        $controller = new \Joindin\Api\Controller\EventHostsController();

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();

        $automatedUserMapper = $controller->getUserMapper($request, $db);

        $this->assertInstanceOf(Joindin\Api\Model\UserMapper::class, $automatedUserMapper);
        $this->assertSame($automatedUserMapper, $controller->getUserMapper($request, $db));
    }

    public function testThatGettingEventMapperWithoutSettingFirstWorksAsExpected()
    {
        $controller = new \Joindin\Api\Controller\EventHostsController();

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder('\Joindin\Api\Request')->disableOriginalConstructor()->getMock();

        $automatedEventMapper = $controller->getEventMapper($request, $db);

        $this->assertInstanceOf(Joindin\Api\Model\EventMapper::class, $automatedEventMapper);
        $this->assertSame($automatedEventMapper, $controller->getEventMapper($request, $db));
    }
}