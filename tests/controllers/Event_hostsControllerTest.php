<?php

namespace JoindinTest\Controller;


class Event_hostsControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionCode 401
     * @expectedExceptionMessage You must be logged in to create data
     */
    public function testThatNotLoggedInUsersCanNotAddAHost()
    {
        $controller = new \Event_hostsController();

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Event not found
     */
    public function testThatMissingEventThrowsException()
    {
        $controller = new \Event_hostsController();

        $em = $this->getMockBuilder('EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(false);

        $controller->setEventMapper($em);

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id = 2;

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 403
     * @expectedExceptionMessage You do not have permission to add hosts to this event
     */
    public function testThatExceptionIsThrownWhenNonAdminUserTriesToAddHostToEvent()
    {
        $controller = new \Event_hostsController();

        $em = $this->getMockBuilder('EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(false);

        $controller->setEventMapper($em);

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id = 2;

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 404
     * @expectedExceptionMessage No User found
     */
    public function testThatExceptionIsThrownWhenUnknownUserShallBeAddedAsHostToEvent()
    {
        $controller = new \Event_hostsController();

        $em = $this->getMockBuilder('EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(false);

        $controller->setUserMapper($um);

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 'foo'];
        $request->user_id = 2;
        $request->method('getParameter')->willReturn('myhostname');

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Something went wrong
     */
    public function testThatExceptionIsThrownWhenEventHostMapperHasProblems()
    {
        $controller = new \Event_hostsController();

        $em = $this->getMockBuilder('EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(13);

        $controller->setUserMapper($um);

        $ehm = $this->getMockBuilder('EventHostMapper')->disableOriginalConstructor()->getMock();
        $ehm->expects($this->once())->method('addHostToEvent')->with($this->equalTo(12, 13))->willReturn(false);

        $controller->setEventHostMapper($ehm);

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 12];
        $request->user_id = 2;
        $request->method('getParameter')->willReturn('myhostname');

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }

    public function testThatViewGetsCorrectValuesWhenEverythingWorksAsExpected()
    {
        $controller = new \Event_hostsController();

        $em = $this->getMockBuilder('EventMapper')->disableOriginalConstructor()->getMock();
        $em->method('getEventById')->willReturn(true);
        $em->method('thisUserHasAdminOn')->willReturn(true);

        $controller->setEventMapper($em);

        $um = $this->getMockBuilder('UserMapper')->disableOriginalConstructor()->getMock();
        $um->method('getUserIdFromUsername')->with($this->equalTo('myhostname'))->willReturn(13);

        $controller->setUserMapper($um);

        $ehm = $this->getMockBuilder('EventHostMapper')->disableOriginalConstructor()->getMock();
        $ehm->expects($this->once())->method('addHostToEvent')->with($this->equalTo(12), $this->equalTo(13))->willReturn(true);

        $controller->setEventHostMapper($ehm);

        $view = $this->getMockBuilder('\ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->once())->method('setHeader')->with(
            $this->equalTo('Location'),
            $this->equalTo('foo//events/12/hosts')
        );
        $view->expects($this->once())->method('setResponseCode')->with($this->equalTo(201));
        $view->expects($this->once())->method('setNoRender')->with($this->equalTo(true));

        $request = $this->getMockBuilder('\Request')->disableOriginalConstructor()->getMock();
        $request->url_elements = [3 => 12];
        $request->user_id = 2;
        $request->base = 'foo';
        $request->method('getParameter')->willReturn('myhostname');
        $request->method('getView')->willReturn($view);

        $db = $this->getMockBuilder('\PDO')->disableOriginalConstructor()->getMock();

        $controller->addHost($request, $db);
    }
}
