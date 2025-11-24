<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Retry;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Retry\ExponentialBackoff;
use Phpmystic\RetrofitPhp\Retry\FixedBackoff;
use Phpmystic\RetrofitPhp\Retry\LinearBackoff;

class BackoffStrategyTest extends TestCase
{
    public function testExponentialBackoff(): void
    {
        $backoff = new ExponentialBackoff(baseDelayMs: 1000, multiplier: 2.0);

        // First retry: 1000ms
        $this->assertEqualsWithDelta(1000, $backoff->getDelayMs(0), 100);

        // Second retry: 2000ms
        $this->assertEqualsWithDelta(2000, $backoff->getDelayMs(1), 100);

        // Third retry: 4000ms
        $this->assertEqualsWithDelta(4000, $backoff->getDelayMs(2), 100);

        // Fourth retry: 8000ms
        $this->assertEqualsWithDelta(8000, $backoff->getDelayMs(3), 100);
    }

    public function testExponentialBackoffWithMaxDelay(): void
    {
        $backoff = new ExponentialBackoff(baseDelayMs: 1000, multiplier: 2.0, maxDelayMs: 5000);

        $this->assertEqualsWithDelta(1000, $backoff->getDelayMs(0), 100);
        $this->assertEqualsWithDelta(2000, $backoff->getDelayMs(1), 100);
        $this->assertEqualsWithDelta(4000, $backoff->getDelayMs(2), 100);

        // Should cap at 5000ms
        $this->assertEqualsWithDelta(5000, $backoff->getDelayMs(3), 100);
        $this->assertEqualsWithDelta(5000, $backoff->getDelayMs(4), 100);
    }

    public function testExponentialBackoffWithJitter(): void
    {
        $backoff = new ExponentialBackoff(baseDelayMs: 1000, multiplier: 2.0, jitter: true);

        // With jitter, delay should be randomized but within reasonable range
        $delay0 = $backoff->getDelayMs(0);
        $this->assertGreaterThan(0, $delay0);
        $this->assertLessThanOrEqual(1000, $delay0);

        $delay1 = $backoff->getDelayMs(1);
        $this->assertGreaterThan(0, $delay1);
        $this->assertLessThanOrEqual(2000, $delay1);
    }

    public function testFixedBackoff(): void
    {
        $backoff = new FixedBackoff(delayMs: 500);

        // Should always return the same delay
        $this->assertEquals(500, $backoff->getDelayMs(0));
        $this->assertEquals(500, $backoff->getDelayMs(1));
        $this->assertEquals(500, $backoff->getDelayMs(2));
        $this->assertEquals(500, $backoff->getDelayMs(10));
    }

    public function testLinearBackoff(): void
    {
        $backoff = new LinearBackoff(initialDelayMs: 1000, incrementMs: 500);

        // First retry: 1000ms
        $this->assertEquals(1000, $backoff->getDelayMs(0));

        // Second retry: 1500ms
        $this->assertEquals(1500, $backoff->getDelayMs(1));

        // Third retry: 2000ms
        $this->assertEquals(2000, $backoff->getDelayMs(2));

        // Fourth retry: 2500ms
        $this->assertEquals(2500, $backoff->getDelayMs(3));
    }

    public function testLinearBackoffWithMaxDelay(): void
    {
        $backoff = new LinearBackoff(initialDelayMs: 1000, incrementMs: 1000, maxDelayMs: 3000);

        $this->assertEquals(1000, $backoff->getDelayMs(0));
        $this->assertEquals(2000, $backoff->getDelayMs(1));
        $this->assertEquals(3000, $backoff->getDelayMs(2));

        // Should cap at 3000ms
        $this->assertEquals(3000, $backoff->getDelayMs(3));
        $this->assertEquals(3000, $backoff->getDelayMs(4));
    }
}
