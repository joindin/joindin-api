<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Joindin\Api\Controller\EventImagesController;
use Joindin\Api\Model\EventMapper;
use Joindin\Api\Request;
use Joindin\Api\Test\MapperFactoryForTests;
use Joindin\Api\View\ApiView;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class EventImagesControllerTest extends TestCase
{
    const IMAGE_DIR = __DIR__.DIRECTORY_SEPARATOR."images".DIRECTORY_SEPARATOR;

    /**
     * @var MockObject
     */
    private $request;
    /**
     * @var MockObject
     */
    private $db;
    /**
     * @var MapperFactoryForTests
     */
    private $mapperFactory;
    /**
     * @var EventImagesController
     */
    private $sut;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();

        $this->mapperFactory = new MapperFactoryForTests();

        $this->sut = new EventImagesController([], $this->mapperFactory);

        if (!file_exists(self::IMAGE_DIR)) {
            mkdir(self::IMAGE_DIR);
        }
    }

    public function testListImagesWorksAsExpected() {
        $this->request->url_elements[3] = 2;

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("getImages")->with(2)->willReturn("someImage");
        self::assertEquals(['images' => "someImage"], $this->sut->listImages($this->request, $this->db));
    }

    public function testDeleteImageThrowsExceptionWithoutUserId() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to create data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->sut->deleteImage($this->request, $this->db);
    }

    public function testDeleteImageWorksAsExpected() {
        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;
        $this->request->base = "hi";
        $this->request->version = "10";


        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("removeImages")->with(2);

        $this->view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->view->expects(self::once())->method("setHeader")->with("Location", "hi/10/events/2");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::NO_CONTENT);
        $this->request->expects(self::once())->method("getView")->willReturn($this->view);

        $this->sut->deleteImage($this->request, $this->db);
    }

    public function testCreateThrowsExceptionWithoutNoUserId() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must be logged in to create data');
        $this->expectExceptionCode(Http::UNAUTHORIZED);

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenNoEventWithId() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('There is no event with ID 2');

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $this->mapperFactory->getMapperMock($this, EventMapper::class)
            ->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 0]]);

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenNoAdminRights() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("You don't have permission to do that");
        $this->expectExceptionCode(Http::FORBIDDEN);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(false);

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenNoFiles() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Image was not supplied");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES = [];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenFilesWithErrors() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Image upload failed (Code: 4)");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 4
        ];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenFilesWithIncorrectType() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Supplied image must be a PNG, JPG or GIF");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate("bmp")
        ];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenImageNotSquare() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Supplied image must be square");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate("jpg", 400)
        ];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenImageToSmall() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Supplied image must be at least 140px square");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate("jpg", 1, 1)
        ];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenImageToLarge() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Supplied image must be no more than 1440px square");
        $this->expectExceptionCode(Http::BAD_REQUEST);

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate("jpg", 1441, 1441)
        ];

        $this->sut->createImage($this->request, $this->db);
    }

    public function testCreateImagesThrowsExceptionWhenImageCantBeSaved() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("The file could not be saved");

        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate("jpg")
        ];
        $this->request->expects(self::once())->method("getConfigValue")->with("event_image_path")->willReturn("/");
        $this->request->expects(self::once())->method("moveUploadedFile")->willReturn(false);

        $this->sut->createImage($this->request, $this->db);
    }

    /**
     * @dataProvider imageTypeProvider
     */
    public function testCreateImagesWorksAsExpected($type) {
        $this->request->user_id = 5;
        $this->request->url_elements[3] = 2;
        $this->request->base = "hi";
        $this->request->version = "10";

        $eventMapper = $this->mapperFactory->getMapperMock($this, EventMapper::class);
        $eventMapper->expects(self::once())->method("getEventById")->with(2)->willReturn(['meta' => ["count" => 1]]);
        $eventMapper->expects(self::once())->method("thisUserHasAdminOn")->with(2)->willReturn(true);
        $_FILES['image'] = [
            'error' => 0,
            'tmp_name' => $this->imageCreate($type)
        ];

        $this->request->expects(self::once())->method("getConfigValue")->with("event_image_path")->willReturn(self::IMAGE_DIR);
        $this->request->expects(self::once())->method("moveUploadedFile")->willReturn(true);
        copy($_FILES['image']['tmp_name'], self::IMAGE_DIR."icon-2-orig.".$type);
        copy($_FILES['image']['tmp_name'], self::IMAGE_DIR."icon-2-small.".$type);
        $eventMapper->expects(self::once())->method("removeImages")->with(2);
        $eventMapper->expects(self::exactly(2))->method("saveNewImage")->withConsecutive([2, "icon-2-orig.".$type,500,500, "orig"], [2, "icon-2-small.".$type,140,140, "small"]);

        $this->view = $this->getMockBuilder(ApiView::class)->disableOriginalConstructor()->getMock();
        $this->view->expects(self::once())->method("setHeader")->with("Location", "hi/10/events/2");
        $this->view->expects(self::once())->method("setResponseCode")->with(Http::CREATED);
        $this->request->expects(self::once())->method("getView")->willReturn($this->view);

        $this->sut->createImage($this->request, $this->db);
    }

    public function imageTypeProvider()
    {
        return [
            ["gif"],
            ["jpg"],
            ["png"]
        ];
    }

    protected function imageCreate(string $extention, int $width = 500, int $height = 500) {
        $imageName = self::IMAGE_DIR."testImage.".$extention;
        $image = imagecreatetruecolor($width, $height);
        imagetruecolortopalette($image, false, 2);
        try {
            $function = "image".($extention == "jpg" ? "jpeg" : $extention);
            $function($image, $imageName);
            unset($image);
            return $imageName;
        } catch (\Exception $e) {
            throw new \Exception('You have no permission to create files in this directory:' . $e);
        }
    }

    protected function tearDown(): void
    {
        if(file_exists(self::IMAGE_DIR)) {
            foreach (scandir(self::IMAGE_DIR) as $image) {
                if(is_file(self::IMAGE_DIR.$image)) {
                    unlink(self::IMAGE_DIR.$image);
                }
            }
            rmdir(self::IMAGE_DIR);
        }
    }
}
