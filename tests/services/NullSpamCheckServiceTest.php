<?php

namespace JoindinTest\Services;

use PHPUnit\Framework\TestCase;

class NullSpamCheckServiceTest extends TestCase
{
    /**
     * @test
     */
    public function spamCheckShouldReturnTrue()
    {
        $service = new \NullSpamCheckService();
        $this->assertTrue($service->isCommentAcceptable([], '0.0.0.0', 'userAgent'));
    }
}
