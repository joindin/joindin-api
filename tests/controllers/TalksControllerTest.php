<?php
namespace JoindinTest\Controller;

require_once __DIR__ . '/../../src/inc/Request.php';

class TalksControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testTalkCommentEmail()
    {
        $_SERVER['PATH_INFO'] = 'v2.1/talks/8427/comments?comment=TADA';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request  = new \Request();

        var_dump($request);die;


    }


}