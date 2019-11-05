<?php

namespace Joindin\Api\Test\View;

use Joindin\Api\View\ApiView;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

/**
 * @covers \Joindin\Api\View\ApiView
 */
final class ApiViewTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThatAReturnCodeSetViaHeaderIsReturnedWhenNoOtherReturnCodeIsSet()
    {
        $view = new ApiView();

        header('Foo: bar', false, Http::CREATED);

        ob_start();
        $view->render('');
        $this->assertEquals('', ob_get_contents());
        ob_end_clean();

        $this->assertEquals(Http::CREATED, http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThatAReturnCodeSetViaSetHeaderIsReturnedEvenThoughAHeaderCodeIsSet()
    {
        $view = new ApiView();

        $view->setResponseCode(Http::ACCEPTED);
        header('Foo: bar', false, Http::CREATED);

        ob_start();
        $view->render('');
        $this->assertEquals('', ob_get_contents());
        ob_end_clean();

        $this->assertEquals(Http::ACCEPTED, http_response_code());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThatHeadersAreSet()
    {
        $view = new ApiView();

        $view->setResponseCode(Http::ACCEPTED);
        header('Foo: bar', false, Http::CREATED);

        ob_start();
        $view->render('');
        $this->assertEquals('', ob_get_contents());
        ob_end_clean();

        $expectedHeaders = [
            'Foo: bar',
        ];

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Test will run when xdebug enabled');
        }
        $this->assertEquals($expectedHeaders, xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThatHeadersAreSetViaSetHeaders()
    {
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Test will run when xdebug enabled');
        }

        $view = new ApiView();

        $view->setHeader('Bar', 'Foo');

        $view->render('');

        $expectedHeaders = [
            'Bar: Foo',
        ];
        $this->assertEquals($expectedHeaders, xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testThatSettingHeadersDoesntOverwriteIntendedHeaders()
    {
        $view = new ApiView();
        $view->setResponseCode(Http::CREATED);
        $view->setHeader('Location', 'http://example.org');

        ob_start();
        $view->render('');
        ob_end_clean();

        $expectedHeaders = [
            'Location: http://example.org',
        ];

        $this->assertEquals(Http::CREATED, http_response_code());

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('Test will run when xdebug enabled');
        }
        $this->assertEquals($expectedHeaders, xdebug_get_headers());
    }
}
