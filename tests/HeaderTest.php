<?php
/**
 * HeaderTest.php
 * User: adear
 * Date: 9/14/14
 * Time: 12:24 PM
 *
 *
 */

namespace Joindin\Api\Test;

use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{

    public function testParseParamsWithEmbededSeparator()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3';
        $header = new \Joindin\Api\Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $this->assertEquals(3, $header->count());
    }
    public function testParseParamsWithTwoGlues()
    {
        $headerStr = 'For=10.0.0.1,For=10.0.0.2;user-agent="test;test;test;test";For=10.0.0.3;user-agent="secondLevel;some date"';
        $header = new \Joindin\Api\Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $header->setGlue(',');
        $header->parseParams();
        $this->assertEquals(5, $header->count());
    }
    public function testBuildEntityArray()
    {
        $headerStr = 'For=10.0.0.1;user-agent="test;test;test;test";For=10.0.0.2;user-agent="secondLevel;
        some date";for=10.0.0.3;user-agent="thirdLevel"';
        $header = new \Joindin\Api\Header('Forwarded', $headerStr, ';');

        $header->parseParams();
        $header->setGlue(',');
        $header->parseParams();
        $entityArray = $header->buildEntityArray();
        $this->assertEquals(3, count($entityArray['For']));
        $this->assertEquals(3, count($entityArray['User-agent']));
    }
    public function testBuildEntityArrayWithValueOnly()
    {
        $headerStr = '10.0.0.1,10.0.0.2,10.0.0.3';
        $header = new \Joindin\Api\Header('X-Forwarded-For', $headerStr, ',');
        $header->parseParams();
        $this->assertEquals(3, $header->count());
        $partsArray = $header->toArray();
        $this->assertEquals('10.0.0.1', $partsArray[0]);
        $entityArray = $header->buildEntityArray();
        $this->assertEquals(3, count($entityArray[0]));
    }
}
