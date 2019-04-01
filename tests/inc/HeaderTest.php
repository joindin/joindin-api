<?php

namespace JoindinTest\Inc;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/inc/Header.php';

class HeaderTest extends TestCase
{

    public function testParseParamsWithEmbededSeparator()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3';
        $header = new \Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $this->assertEquals(3, $header->count());
    }
    public function testParseParamsWithTwoGlues()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3;user-agent="secondLevel;some date"';
        $header = new \Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $header->setGlue(',');
        $header->parseParams();
        $this->assertEquals(5, $header->count());
    }
    public function testBuildEntityArray()
    {
        $headerStr = 'For=10.0.0.1;user-agent="test;test;test;test";For=10.0.0.2;user-agent="secondLevel;
        some date";for=10.0.0.3;user-agent="thirdLevel"';
        $header = new \Header('Forwarded', $headerStr, ';');

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
        $header = new \Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $this->assertEquals(3, $header->count());
        $partsArray = $header->toArray();
        $this->assertEquals('10.0.0.1', $partsArray[0]);
        $entityArray = $header->buildEntityArray();
        $this->assertCount(3, $entityArray[0]);
    }
}
