<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\Timeout;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Timeout;
use Phpmystic\RetrofitPhp\Internal\ServiceMethod;
use ReflectionMethod;

interface TestApiWithTimeouts
{
    #[GET('/quick')]
    #[Timeout(5)]
    public function getQuickData(): array;

    #[GET('/slow')]
    #[Timeout(120)]
    public function getSlowData(): array;

    #[POST('/upload')]
    #[Timeout(300)]
    public function uploadLargeFile(array $data): array;

    #[GET('/default')]
    public function getDefaultTimeout(): array;
}

class TimeoutTest extends TestCase
{
    public function testTimeoutAttributeCanBeRead(): void
    {
        $reflection = new ReflectionMethod(TestApiWithTimeouts::class, 'getQuickData');
        $attributes = $reflection->getAttributes(Timeout::class);

        $this->assertCount(1, $attributes);

        $timeout = $attributes[0]->newInstance();
        $this->assertEquals(5, $timeout->seconds);
    }

    public function testDifferentTimeoutsForDifferentMethods(): void
    {
        $quickMethod = new ReflectionMethod(TestApiWithTimeouts::class, 'getQuickData');
        $quickTimeout = $quickMethod->getAttributes(Timeout::class)[0]->newInstance();
        $this->assertEquals(5, $quickTimeout->seconds);

        $slowMethod = new ReflectionMethod(TestApiWithTimeouts::class, 'getSlowData');
        $slowTimeout = $slowMethod->getAttributes(Timeout::class)[0]->newInstance();
        $this->assertEquals(120, $slowTimeout->seconds);

        $uploadMethod = new ReflectionMethod(TestApiWithTimeouts::class, 'uploadLargeFile');
        $uploadTimeout = $uploadMethod->getAttributes(Timeout::class)[0]->newInstance();
        $this->assertEquals(300, $uploadTimeout->seconds);
    }

    public function testMethodWithoutTimeoutAttribute(): void
    {
        $reflection = new ReflectionMethod(TestApiWithTimeouts::class, 'getDefaultTimeout');
        $attributes = $reflection->getAttributes(Timeout::class);

        $this->assertCount(0, $attributes);
    }
}
