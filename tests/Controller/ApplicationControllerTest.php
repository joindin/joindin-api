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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class ApplicationControllerTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $request;
    /**
     * @var MockObject
     */
    private $db;
    /**
     * @var ApplicationsController
     */
    private $sut;
    /**
     * @var MockObject
     */
    private $clientMapper;
    private $clientModelCollection;
    /**
     * @var MockObject
     */
    private $apiView;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $this->apiView = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->clientModelCollection = $this->getMockBuilder(ClientModelCollection::class)->disableOriginalConstructor()->getMock();
        $this->clientMapper = $this->getMockBuilder(ClientMapper::class)->disableOriginalConstructor()->getMock();
        $this->sut = new ApplicationsController();
        $this->sut->setClientMapper($this->clientMapper);
    }

    /**
     * @dataProvider notLoggedInUserCallableProvider
     */
    public function testThatNotLoggedInUsersThrowsExceptions($action)
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        call_user_func($action, $this->request, $this->db);
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
        $this->request->user_id = 2;

        $this->clientModelCollection->expects(self::once())->method("getOutputView")->with($this->request,
            false)->willReturn([]);
        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($this->clientModelCollection);

        self::assertEquals([], $this->sut->getApplication($this->request, $this->db));
    }

    public function testThatListApplicationsWorksAsExpected()
    {
        $this->request->user_id = 2;
        $this->request->paginationParameters = ['resultsperpage' => 1, 'start' => ""];

        $this->clientModelCollection->expects(self::once())->method("getOutputView")->with($this->request,
            false)->willReturn([]);
        $this->clientMapper->expects(self::once())->method("getClientsForUser")->with(2, 1,
            "")->willReturn($this->clientModelCollection);

        self::assertEquals([], $this->sut->listApplications($this->request, $this->db));
    }

    public function testThatDeleteApplicationThrowsExceptionWhenNoClients()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No application found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->user_id = 2;
        $this->clientModelCollection->expects(self::once())->method("getClients")->willReturn(null);
        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($this->clientModelCollection);

        $this->sut->deleteApplication($this->request, $this->db);
    }

    public function testThatDeleteApplicationThrowsExceptionWhenDeleteFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Embedded exception message');
        $this->expectExceptionCode(Http::INTERNAL_SERVER_ERROR);

        $this->request->user_id = 2;
        $this->request->url_elements[3] = 1;

        $this->clientModelCollection->expects(self::once())->method("getClients")->willReturn(true);

        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($this->clientModelCollection);
        $this->clientMapper->expects(self::once())->method("deleteClient")->with(1)->willThrowException(new Exception('Embedded exception message'));

        $this->sut->deleteApplication($this->request, $this->db);
    }

    public function testThatDeleteApplicationWorksAsExpected()
    {
        $this->apiView->expects(self::once())->method("setNoRender")->with(true);
        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::NO_CONTENT);
        $this->apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications');

        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->request->expects(self::exactly(3))->method("getView")->willReturn($this->apiView);
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->request->user_id = 2;
        $this->request->url_elements[3] = 1;
        $this->request->base = 'hi';
        $this->request->version = '10';

        $this->clientModelCollection->expects(self::once())->method("getClients")->willReturn(true);

        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->willReturn($this->clientModelCollection);
        $this->clientMapper->expects(self::once())->method("deleteClient")->with(1);

        $this->sut->deleteApplication($this->request, $this->db);
    }

    public function testCreateApplicationWithErrorsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("'name' is a required field. 'description' is a required field. 'callback_url' is a required field");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 2;
        $this->request->expects(self::any())->method("getParameter")->willReturn("");

        $this->sut->createApplication($this->request, $this->db);
    }

    public function testCreateApplicationWorksAsExpected()
    {
        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::CREATED);
        $this->apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications/5');

        $this->request->expects(self::exactly(2))->method("getView")->willReturn($this->apiView);
        $this->request->user_id = 2;
        $this->request->base = "hi";
        $this->request->version = "10";
        $this->request->expects(self::any())->method("getParameter")->willReturn("name", "description", "callback_url");

        $this->clientModelCollection->expects(self::once())->method("getOutputView")->with($this->request)->willReturn("hello there");

        $this->clientMapper->expects(self::once())->method("createClient")->willReturn(5);
        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->with(5,
            2)->willReturn($this->clientModelCollection);

        $this->assertEquals("hello there", $this->sut->createApplication($this->request, $this->db));
    }

    public function testEditApplicationWithErrorsThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("'name' is a required field. 'description' is a required field");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 2;
        $this->request->expects(self::any())->method("getParameter")->willReturn("");

        $this->sut->editApplication($this->request, $this->db);
    }

    public function testEditApplicationWorksAsExpected()
    {
        $expectedApp = [
            "name" => "name",
            "description" => "description",
            "callback_url" => "callback_url",
            "user_id" => 2,
        ];

        $this->apiView->expects(self::once())->method("setResponseCode")->with(Http::CREATED);
        $this->apiView->expects(self::once())->method("setHeader")->with('Location', 'hi/10/applications/1');

        $this->request->expects(self::exactly(2))->method("getView")->willReturn($this->apiView);
        $this->request->user_id = $expectedApp["user_id"];
        $this->request->base = "hi";
        $this->request->version = "10";
        $this->request->url_elements[3] = 1;

        $this->request->expects(self::any())->method("getParameter")->willReturn($expectedApp["name"],
            $expectedApp["description"], $expectedApp["callback_url"]);

        $this->clientModelCollection->expects(self::once())->method("getOutputView")->with($this->request)->willReturn("hello there");

        $this->clientMapper->expects(self::once())->method("updateClient")->with(1, $expectedApp)->willReturn(1);
        $this->clientMapper->expects(self::once())->method("getClientByIdAndUser")->with(1,
            $expectedApp["user_id"])->willReturn($this->clientModelCollection);

        $this->assertEquals("hello there", $this->sut->editApplication($this->request, $this->db));
    }
}
