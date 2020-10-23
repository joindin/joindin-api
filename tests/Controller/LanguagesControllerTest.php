<?php


namespace Joindin\Api\Test\Controller;


use Exception;
use Joindin\Api\Controller\LanguagesController;
use Joindin\Api\Model\LanguageMapper;
use Joindin\Api\Request;
use Joindin\Api\Test\MapperFactoryForTests;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

/**
 * @property MockObject db
 * @property MockObject request
 * @property LanguagesController sut
 * @property MapperFactoryForTests mapperFactory
 */
class LanguagesControllerTest extends TestCase
{
    protected function setUp(): void
    {
        $this->db = $this->getMockBuilder(PDO::class)->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mapperFactory = new MapperFactoryForTests();
        $this->sut = new LanguagesController([], $this->mapperFactory);
    }

    public function testGetLanguagesThrowsExceptionIfLanguageNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Language not found');
        $this->expectExceptionCode(Http::NOT_FOUND);

        $this->request->url_elements[3] = 5;
        $mapper = $this->mapperFactory->getMapperMock($this, LanguageMapper::class);
        $mapper->expects(self::once())->method("getLanguageById")->with(5, false)->willReturn(['languages' => []]);

        $this->sut->getLanguage($this->request, $this->db);
    }

    public function testGetLanguagesWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $mapper = $this->mapperFactory->getMapperMock($this, LanguageMapper::class);
        $mapper->expects(self::once())->method("getLanguageById")->with(5, false)->willReturn(['languages' => ["EN"]]);

        $this->sut->getLanguage($this->request, $this->db);
    }

    public function testGetAllLanguagesWorksAsExpected()
    {
        $this->request->url_elements[3] = 5;
        $this->request->paginationParameters['start'] = 0;
        $this->request->paginationParameters['resultsperpage'] = 10;
        $mapper = $this->mapperFactory->getMapperMock($this, LanguageMapper::class);
        $mapper->expects(self::once())->method("getLanguageList")->with(10, 0,
            false)->willReturn(['languages' => ["EN"]]);

        self::assertEquals(['languages' => ["EN"]], $this->sut->getAllLanguages($this->request, $this->db));
    }

}
