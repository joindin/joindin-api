<?php


namespace Joindin\Api\Test\Controller;


use Joindin\Api\Controller\BaseApiController;
use Joindin\Api\Request;
use PHPUnit\Framework\TestCase;

class BaseApiControllerTest extends TestCase
{
    /**
     * @var BaseApiController
     */
    private $sut;
    /**
     * @var Request
     */
    private $request;

    protected function setUp(): void
    {
        $this->request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->sut = new BaseApiControllerImplementation();
    }

    public function testGetItemIdWithoutUrlElementsReturnsFalse()
    {
        self::assertFalse($this->sut->getItemId($this->request));
    }

    public function testGetItemIdWorksAsExpected()
    {
        $this->request->url_elements[3] = 1;
        self::assertEquals(1, $this->sut->getItemId($this->request));
    }

    public function testGetVerbosityReturnsFalseIfVerboseNotSet()
    {
        self::assertFalse($this->sut->getVerbosity($this->request));
    }

    public function testGetVerbosityReturnsFalseIfVerboseNotSetToYes()
    {
        $this->request->parameters['verbose'] = "no";
        self::assertFalse($this->sut->getVerbosity($this->request));
    }

    public function testGetVerbosityReturnsTrueIfVerboseSetToYes()
    {
        $this->request->parameters['verbose'] = "yes";
        self::assertTrue($this->sut->getVerbosity($this->request));
    }

    public function testGetSortReturnsCorrectParameter() {
        $this->request->parameters['sort'] = "some sort of sort";
        self::assertEquals("some sort of sort", $this->sut->getSort($this->request));
    }

    public function testGetRequestParameterReturnsDefaultIfNotSet() {
        self::assertFalse($this->sut->getSort($this->request));
    }
}


class BaseApiControllerImplementation extends BaseApiController
{
}
