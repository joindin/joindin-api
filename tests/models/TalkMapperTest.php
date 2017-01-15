<?php
namespace JoindinTest\Model;

use JoindinTest\Inc\mockPDO;
use PDOStatement;
use PHPUnit_Framework_TestCase;
use Request;
use TalkMapper;

class TalkMapperTest extends PHPUnit_Framework_TestCase
{
    public function testMediaTypesAreAddedCorrectly()
    {
        $request = new Request(
            [],
            [
                'REQUEST_URI' => 'http://api.dev.joind.in/v2.1/talks/3?verbose=yes',
                'REQUEST_METHOD' => 'GET'
            ]
        );

        $mockdb = $this->getMockBuilder(mockPDO::class)->getMock();
        $stmt = $this->getMockBuilder(PDOStatement::class)
                ->setMethods(["execute", 'fetchAll'])
                ->getMock();

        $stmt->method("execute")->willReturn(true);

        $stmt->method("fetchAll")->willReturn(
            $this->getValidMediaRows()
        );

        $mockdb->method('prepare')
            ->willReturn($stmt);

        $talk_mapper = new TalkMapper($mockdb, $request);
        $talk = [
            [
                'ID' => 3,
            ]
        ];
        $talk = $talk_mapper->addTalkMediaTypes($talk);
        
        $this->assertSame(
            'https://slideshare.net',
            $talk[0]['slides_link']
        );

        $this->assertSame(
            $this->transformMediaRows($this->getValidMediaRows()),
            $talk[0]['talk_media']
        ); 
    }

    private function getValidMediaRows()
    {
        return [
            [
                'display_name' => "slides_link",
                'url' => "https://slideshare.net",
            ],
            [
                'display_name' => "video_link",
                'url' => "https://youtube.com",
            ]
        ];
    }

    private function transformMediaRows($rows)
    {
        $transformedRows = [];

        foreach ($rows as $row) {
               $transformedRows[] = [$row['display_name'] => $row['url']];
        }

        return $transformedRows;
    }
}
