<?php


namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\ApplicationsController;
use Joindin\Api\Model\ClientMapper;
use Joindin\Api\Model\ClientModel;
use Joindin\Api\Model\ClientModelCollection;
use Joindin\Api\Request;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class ApplicationControllerTest extends TestCase
{
    /**
     * @dataProvider notLoggedInUserCallableProvider
     */
    public function testThatNotLoggedInUsersThrowsExceptions($action)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        call_user_func($action, $request, $db);
    }

    public function notLoggedInUserCallableProvider()
    {
        $controller = new ApplicationsController();

        return [
            [[$controller, "getApplication"]],
            [[$controller, "listApplications"]],
            [[$controller, "createApplication"]],
            [[$controller, "editApplication"]],
            [[$controller, "deleteApplication"]],
        ];
    }

    public function testThatGetApplicationWorksAsExpected()
    {
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getOutputView")->with($request, false)->willReturn([]);

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($clientModelCollection);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        self::assertEquals([], $controller->getApplication($request, $db));
    }

    public function testThatListApplicationsWorksAsExpected()
    {
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;
        $request->paginationParameters = ['resultsperpage' => 1, 'start' => ""];

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getOutputView")->with($request, false)->willReturn([]);

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("getClientsForUser")->with(2, 1,
            "")->willReturn($clientModelCollection);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        self::assertEquals([], $controller->listApplications($request, $db));
    }

    public function testThatDeleteApplicationThrowsExceptionWhenNoClients()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No application found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getClients")->willReturn(null);

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($clientModelCollection);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $controller->deleteApplication($request, $db);
    }

    public function testThatDeleteApplicationThrowsExceptionWhenDeleteFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Embedded exception message');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;
        $request->url_elements[3] = 1;

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getClients")->willReturn(true);

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($clientModelCollection);
        $clientMapper->expects(self::once())->method("deleteClient")->with(1)->willThrowException(new Exception('Embedded exception message'));

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $controller->deleteApplication($request, $db);
    }

    public function testThatDeleteApplicationWorksAsExpected()
    {
        $apiView = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $apiView->expects(self::once())->method("setNoRender")->with(true);
        $apiView->expects(self::once())->method("setResponseCode")->with(Http::NO_CONTENT);
        $apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications');

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects(self::exactly(3))->method("getView")->willReturn($apiView);
        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;
        $request->url_elements[3] = 1;
        $request->base = 'hi';
        $request->version = '10';

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getClients")->willReturn(true);

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($clientModelCollection);
        $clientMapper->expects(self::once())->method("deleteClient")->with(1);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $controller->deleteApplication($request, $db);
    }

    public function testCreateApplicationWithErrorsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("'name' is a required field. 'description' is a required field. 'callback_url' is a required field");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;
        $request->expects(self::any())->method("getParameter")->willReturn("");

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $controller->createApplication($request, $db);
    }

    public function testCreateApplicationWorksAsExpected()
    {
        $apiView = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $apiView->expects(self::once())->method("setResponseCode")->with(Http::CREATED);
        $apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications/5');

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects(self::exactly(2))->method("getView")->willReturn($apiView);
        $request->user_id = 2;
        $request->base = "hi";
        $request->version = "10";

        $request->expects(self::any())->method("getParameter")->willReturn("name", "description", "callback_url");

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getOutputView")->with($request)->willReturn("hello there");

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("createClient")->willReturn(5);
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->with(5,
            2)->willReturn($clientModelCollection);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $this->assertEquals("hello there", $controller->createApplication($request, $db));
    }

    public function testEditApplicationWithErrorsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("'name' is a required field. 'description' is a required field");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->user_id = 2;
        $request->expects(self::any())->method("getParameter")->willReturn("");

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $controller->editApplication($request, $db);
    }

    public function testEditApplicationWorksAsExpected()
    {
        $expectedApp = [
            "name" => "name",
            "description" => "description",
            "callback_url" => "callback_url",
            "user_id" => 2,
        ];

        $apiView = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $apiView->expects(self::once())->method("setResponseCode")->with(Http::CREATED);
        $apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications/1');

        $db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $request->expects(self::exactly(2))->method("getView")->willReturn($apiView);
        $request->user_id = $expectedApp["user_id"];
        $request->base = "hi";
        $request->version = "10";
        $request->url_elements[3] = 1;

        $request->expects(self::any())->method("getParameter")->willReturn($expectedApp["name"],
            $expectedApp["description"], $expectedApp["callback_url"]);

        $clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $clientModelCollection->expects(self::once())->method("getOutputView")->with($request)->willReturn("hello there");

        $clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $clientMapper->expects(self::once())->method("updateClient")->with(1, $expectedApp)->willReturn(1);
        $clientMapper->expects(self::once())->method("getClientByIdAndUser")->with(1,
            $expectedApp["user_id"])->willReturn($clientModelCollection);

        $controller = new ApplicationsController();
        $controller->setClientMapper($clientMapper);

        $this->assertEquals("hello there", $controller->editApplication($request, $db));
    }
}
