<?php

namespace Joindin\Api\Test;

use Joindin\Api\Header;
use PHPUnit\Framework\TestCase;

final class HeaderTest extends TestCase
{
    public function testParseParamsWithEmbededSeparator()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3';
        $header    = new Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $this->assertEquals(3, $header->count());
    }

    public function testParseParamsWithTwoGlues()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3;user-agent="secondLevel;some date"';
        $header    = new Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $header->setGlue(',');
        $header->parseParams();
        $this->assertEquals(5, $header->count());
        $this->assertEquals(
            [
                'user-agent="test;test;test;test"',
                'For=10.0.0.3',
                'user-agent="secondLevel;some date"',
                'For=10.0.0.1',
                'For=10.0.0.2',
            ],
            $header->toArray()
        );
    }

    public function testBuildEntityArray()
    {
        $headerStr = 'For=10.0.0.1;user-agent="test;test;test;test";For=10.0.0.2;user-agent="secondLevel;
        some date";for=10.0.0.3;user-agent="thirdLevel"';
        $header    = new Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $header->setGlue(',');
        $header->parseParams();
        $entityArray = $header->buildEntityArray();
        $this->assertCount(3, $entityArray['For']);
        $this->assertCount(3, $entityArray['User-agent']);
    }

    public function testBuildEntityArrayWithValueOnly()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $this->assertEquals(3, $header->count());
        $partsArray = $header->toArray();
        $this->assertEquals('10.0.0.1', $partsArray[0]);
        $entityArray = $header->buildEntityArray();
        $this->assertCount(3, $entityArray[0]);
    }

    /**
     * @test
     */
    public function getNameReturnsTheHeaderName()
    {
        $name   = uniqid('headername', true);
        $header = new Header($name, '', ',');
        $this->assertEquals($name, $header->getName());
    }

    /**
     * @test
     */
    public function getGlueReturnsGlueValue()
    {
        $glue   = uniqid('glue', true);
        $header = new Header('X-Forwarded-For', '', $glue);
        $this->assertEquals($glue, $header->getGlue());
    }

    /**
     * @test
     */
    public function getIteratorWorks()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $iterator = $header->getIterator();

        $values = [];

        foreach ($iterator as $value) {
            $values[] = $value;
        }
        $this->assertEquals('10.0.0.1', $values[0]);
        $this->assertEquals('10.0.0.2', $values[1]);
        $this->assertEquals('10.0.0.3', $values[2]);
    }

    /**
     * @test
     */
    public function toStringWorks()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $this->assertEquals($headerStr, (string) $header);
        $header->parseParams();
        $this->assertEquals('10.0.0.1, 10.0.0.2, 10.0.0.3', (string) $header);
    }

    /**
     * @test
     */
    public function removeValueWorks()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $header->removeValue('10.0.0.2');
        $this->assertEquals(['10.0.0.1', '10.0.0.3'], $header->toArray());
    }

    /**
     * @test
     */
    public function hasValueReturnsTrueIfValueFound()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $this->assertTrue($header->hasValue('10.0.0.1'));
        $this->assertTrue($header->hasValue('10.0.0.2'));
        $this->assertTrue($header->hasValue('10.0.0.3'));
    }

    /**
     * @test
     */
    public function hasValueReturnsFalseIfValueNotFound()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header    = new Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $this->assertFalse($header->hasValue('10.0.0.4'));
    }

    /**
     * @test
     */
    public function setNameCanBeUsedAfterHeadersAreCreated()
    {
        $headerStr = 'application/json';
        $header    = new Header('Accept', $headerStr, '/');
        $this->assertEquals('Accept', $header->getName());
        $header->setName('Content-Type');
        $this->assertEquals('Content-Type', $header->getName());
        $header->parseParams();
        $this->assertEquals(['application', 'json'], $header->toArray());
    }

    /**
     * @test
     */
    public function coverageForParseParams()
    {
        $headerChunks = [
            ';abc',
        ];
        $header       = new Header('Foo', $headerChunks, ';');
        $header->parseParams();
        $header->setGlue('=');
        $header->parseParams();
        $this->assertEquals(
            [
                '',
                'abc',
            ],
            $header->toArray()
        );
    }
}
