<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Controller;

use Exception;
use Joindin\Api\Controller\TalkTypesController;
use Joindin\Api\Model\TalkTypeMapper;
use Joindin\Api\Request;
use Joindin\Api\Test\MapperFactoryForTests;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

class TalkTypesControllerTest extends TestCase
{
    /** @var Request|MockObject */
    private $request;

    /** @var PDO|MockObject */
    private $db;

    /** @var TalkTypesController */
    private $controller;
    /**
     * @var MapperFactoryForTests
     */
    private $mapperFactory;

    public function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->request->paginationParameters = [
            'start' => 1,
            'resultsperpage' => 10,
        ];

        $this->db = $this->createMock(PDO::class);
        $this->mapperFactory = new MapperFactoryForTests();
        $this->controller = new TalkTypesController([], $this->mapperFactory);
    }

    public function testGetAllTalkTypesWithoutAnyTalkType(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')
            ->willReturn(false);

        $this->db->method('prepare')
            ->willReturn($stmt);

        $result = $this->controller->getAllTalkTypes($this->request, $this->db);

        $this->assertFalse($result);
    }

    public function testGetAllTalkTypesWithTalkTypes(): void
    {
        $stmt = $this->createMock(PDOStatement::class);

        $stmt->method('execute')
            ->willReturn(true);

        $stmt->method('fetchAll')
            ->willReturn([['ID' => 1, 'cat_title' => 'Talk', 'cat_desc' => 'Talk']]);

        $stmt->method('fetchColumn')
            ->willReturn(1);

        $this->db->method('prepare')
            ->willReturn($stmt);

        $expected = [
            'talk_types' => [
                [
                    'title' => 'Talk',
                    'description' => 'Talk',
                    'uri' => '//talk_types/1',
                    'verbose_uri' => '//talk_types/1?verbose=yes',
                ]
            ],
            'meta' => [
                'count' => 1,
                'total' => 1,
                'this_page' => '?start=1&resultsperpage=10',
                'prev_page' => '?start=0&resultsperpage=10',
            ]
        ];

        $result = $this->controller->getAllTalkTypes($this->request, $this->db);

        $this->assertSame($expected, $result);
    }

    public function testGetTalkTypeWithTalkType(): void
    {
        $this->request->url_elements[3] = 1;

        $stmt = $this->createMock(PDOStatement::class);

        $stmt->method('execute')
            ->willReturn(true);

        $stmt->method('fetchAll')
            ->willReturn([['ID' => 1, 'cat_title' => 'Talk', 'cat_desc' => 'Talk']]);

        $stmt->method('fetchColumn')
            ->willReturn(1);

        $this->db->method('prepare')
            ->willReturn($stmt);

        $expected = [
            'talk_types' => [
                [
                    'title' => 'Talk',
                    'description' => 'Talk',
                    'uri' => '//talk_types/1',
                    'verbose_uri' => '//talk_types/1?verbose=yes',
                ]
            ],
            'meta' => [
                'count' => 1,
                'total' => 1,
                'this_page' => '?start=1&resultsperpage=10',
                'prev_page' => '?start=0&resultsperpage=10',
            ]
        ];

        $result = $this->controller->getTalkType($this->request, $this->db);

        $this->assertSame($expected, $result);
    }

    public function testGetTalkTypeThrowsExceptionIfTalkNotFound() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talk type not found');
        $this->expectExceptionCode(Http::NOT_FOUND);
        $this->request->url_elements[3] = 1;

        $this->mapperFactory->getMapperMock($this, TalkTypeMapper::class)
            ->expects(self::once())->method("getTalkTypeById")->with(1, false)->willReturn(['talk_types' => []]);

        $this->controller->getTalkType($this->request, $this->db);
    }
}
