<?php

namespace Joindin\Api\Test\Service;

use PHPUnit\Framework\TestCase;

class NullSpamCheckServiceTest extends TestCase
{
    /**
     * @test
     */
    public function spamCheckShouldReturnTrue()
    {
        $service = new \Joindin\Api\Service\NullSpamCheckService();
        $this->assertTrue($service->isCommentAcceptable([], '0.0.0.0', 'userAgent'));
    }
}
