<?php

namespace Joindin\Api\Test\Model;

use DateInterval;
use DateTimeImmutable;
use Joindin\Api\Exception\RateLimitExceededException;
use Joindin\Api\Model\TalkCommentMapper;
use Joindin\Api\Request;
use Joindin\Api\Test\Mock\mockPDO;
use PDOStatement;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TalkCommentMapperTest extends TestCase
{
    public function testCheckRateLimitDoesNotThrowWhenNoRatesAreSet(): void
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('rowCount')
            ->willReturn(0);

        $pdo = $this->getMockBuilder(mockPDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->willReturn($stmt);

        $mapper = new TalkCommentMapper($pdo, new Request([1], [2]));

        $mapper->checkRateLimit(12);

        self::assertTrue(true);
    }

    public function testCheckRateLimitDoesNotThrowWhenRateIsBelowThreshold(): void
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('rowCount')
            ->willReturn(1);

        $stmt->method('fetch')
            ->willReturn(['cnt' => 1]);

        $pdo = $this->getMockBuilder(mockPDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->willReturn($stmt);

        $mapper = new TalkCommentMapper($pdo, new Request([1], [2]));

        $mapper->checkRateLimit(12);

        self::assertTrue(true);
    }

    public function testCheckRateLimitDoesThrowWhenRateIsAboveThreshold(): void
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('rowCount')
            ->willReturn(1);

        $stmt->method('fetch')
            ->willReturn(['cnt' => 61, 'created_at' => (new DateTimeImmutable())->format('c')]);

        $pdo = $this->getMockBuilder(mockPDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->willReturn($stmt);

        $mapper = new TalkCommentMapper($pdo, new Request([1], [2]));

        self::expectException(RateLimitExceededException::class);

        $mapper->checkRateLimit(12);
    }

    /**
     * @throws RateLimitExceededException
     *
     * This tests a condition that should never ever happen as it assumes that the information returned from the
     * Database contains a date that is earlier than the date that we are actually comparing for in the database.
     * So this should never be possible.
     */
    public function testCheckRateLimitDoesNotThrowWhenRateIsAboveThresholdButBelowDuration(): void
    {
        $stmt = $this->getMockBuilder(PDOStatement::class)->disableOriginalConstructor()->getMock();
        $stmt->method('rowCount')
            ->willReturn(1);

        $stmt->method('fetch')
            ->willReturn(['cnt' => 61, 'created_at' => (new DateTimeImmutable())->sub(new DateInterval('PT90M'))->format('c')]);

        $pdo = $this->getMockBuilder(mockPDO::class)->disableOriginalConstructor()->getMock();
        $pdo->method('prepare')
            ->willReturn($stmt);

        $mapper = new TalkCommentMapper($pdo, new Request([1], [2]));

        $mapper->checkRateLimit(12);

        Assert::assertTrue(true);
    }
}
