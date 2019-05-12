<?php

namespace JoindinTest\Services;

use PHPUnit\Framework\TestCase;

class NullSpamCheckServiceTest extends TestCase
{
    public function testSpamCheckShouldReturnTrue()
    {
        $service = new \NullSpamCheckService();
        $this->assertTrue($service->isCommentAcceptable([], '0.0.0.0', 'userAgent'));
    }
}
