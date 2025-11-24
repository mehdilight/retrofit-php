<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\FileHandling;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Streaming;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\FileHandling\FileUpload;
use Phpmystic\RetrofitPhp\FileHandling\ProgressCallback;
use Psr\Http\Message\StreamInterface;

interface ProgressApi
{
    #[POST('/upload')]
    #[Multipart]
    public function uploadWithProgress(
        #[Part('file')] FileUpload $file
    ): array;

    #[GET('/download/{file}')]
    #[Streaming]
    public function downloadWithProgress(#[Path('file')] string $filename): StreamInterface;
}

class ProgressCallbackTest extends TestCase
{
    public function testProgressCallbackCreation(): void
    {
        $called = false;
        $callback = new ProgressCallback(function ($downloadedBytes, $totalBytes) use (&$called) {
            $called = true;
        });

        $this->assertInstanceOf(ProgressCallback::class, $callback);

        // Invoke the callback
        $callback(100, 1000);
        $this->assertTrue($called);
    }

    public function testProgressCallbackReceivesCorrectParameters(): void
    {
        $receivedDownloaded = null;
        $receivedTotal = null;

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$receivedDownloaded, &$receivedTotal) {
            $receivedDownloaded = $downloaded;
            $receivedTotal = $total;
        });

        $callback(250, 1000);

        $this->assertEquals(250, $receivedDownloaded);
        $this->assertEquals(1000, $receivedTotal);
    }

    public function testProgressCallbackCalculatesPercentage(): void
    {
        $percentages = [];

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$percentages) {
            if ($total > 0) {
                $percentages[] = ($downloaded / $total) * 100;
            }
        });

        $callback(0, 1000);    // 0%
        $callback(250, 1000);  // 25%
        $callback(500, 1000);  // 50%
        $callback(750, 1000);  // 75%
        $callback(1000, 1000); // 100%

        $this->assertEquals([0, 25, 50, 75, 100], $percentages);
    }

    public function testProgressCallbackForUpload(): void
    {
        $uploadProgress = [];

        $callback = new ProgressCallback(function ($uploaded, $total) use (&$uploadProgress) {
            $uploadProgress[] = [
                'uploaded' => $uploaded,
                'total' => $total,
                'percentage' => $total > 0 ? round(($uploaded / $total) * 100, 2) : 0,
            ];
        });

        // Simulate upload progress
        $fileSize = 1024 * 1024; // 1MB
        $chunkSize = 256 * 1024;  // 256KB chunks

        for ($uploaded = 0; $uploaded <= $fileSize; $uploaded += $chunkSize) {
            $callback(min($uploaded, $fileSize), $fileSize);
        }

        $this->assertCount(5, $uploadProgress);
        $this->assertEquals(0, $uploadProgress[0]['percentage']);
        $this->assertEquals(100, $uploadProgress[4]['percentage']);
    }

    public function testProgressCallbackForDownload(): void
    {
        $downloadProgress = [];

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$downloadProgress) {
            $downloadProgress[] = [
                'downloaded' => $downloaded,
                'total' => $total,
                'percentage' => $total > 0 ? round(($downloaded / $total) * 100, 2) : 0,
            ];
        });

        // Simulate download progress
        $fileSize = 5 * 1024 * 1024; // 5MB
        $chunkSize = 1024 * 1024;     // 1MB chunks

        for ($downloaded = 0; $downloaded <= $fileSize; $downloaded += $chunkSize) {
            $callback(min($downloaded, $fileSize), $fileSize);
        }

        $this->assertCount(6, $downloadProgress);
        $this->assertEquals(0, $downloadProgress[0]['percentage']);
        $this->assertEquals(100, $downloadProgress[5]['percentage']);

        // Verify incremental progress
        for ($i = 1; $i < count($downloadProgress); $i++) {
            $this->assertGreaterThanOrEqual(
                $downloadProgress[$i - 1]['percentage'],
                $downloadProgress[$i]['percentage']
            );
        }
    }

    public function testProgressCallbackWithUnknownTotalSize(): void
    {
        $called = false;
        $receivedTotal = null;

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$called, &$receivedTotal) {
            $called = true;
            $receivedTotal = $total;
        });

        // When total size is unknown (e.g., chunked encoding)
        $callback(1024, 0);

        $this->assertTrue($called);
        $this->assertEquals(0, $receivedTotal);
    }

    public function testMultipleProgressCallbacksCanBeChained(): void
    {
        $log1 = [];
        $log2 = [];

        $callback1 = new ProgressCallback(function ($downloaded, $total) use (&$log1) {
            $log1[] = $downloaded;
        });

        $callback2 = new ProgressCallback(function ($downloaded, $total) use (&$log2) {
            $log2[] = $downloaded;
        });

        // Simulate progress with both callbacks
        foreach ([100, 200, 300] as $bytes) {
            $callback1($bytes, 1000);
            $callback2($bytes, 1000);
        }

        $this->assertEquals([100, 200, 300], $log1);
        $this->assertEquals([100, 200, 300], $log2);
    }

    public function testProgressCallbackCanCalculateSpeed(): void
    {
        $speeds = [];
        $lastTime = microtime(true);
        $lastBytes = 0;

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$speeds, &$lastTime, &$lastBytes) {
            $currentTime = microtime(true);
            $timeDiff = $currentTime - $lastTime;

            if ($timeDiff > 0 && $downloaded > $lastBytes) {
                $bytesDiff = $downloaded - $lastBytes;
                $speed = $bytesDiff / $timeDiff; // bytes per second
                $speeds[] = round($speed, 2);
            }

            $lastTime = $currentTime;
            $lastBytes = $downloaded;
        });

        // Simulate progress with small delays
        for ($i = 0; $i <= 5; $i++) {
            $callback($i * 1024 * 1024, 5 * 1024 * 1024);
            usleep(10000); // 10ms delay
        }

        $this->assertNotEmpty($speeds);
    }

    public function testProgressCallbackCanCalculateETA(): void
    {
        $etas = [];
        $startTime = microtime(true);

        $callback = new ProgressCallback(function ($downloaded, $total) use (&$etas, $startTime) {
            if ($downloaded > 0 && $total > 0) {
                $elapsed = microtime(true) - $startTime;
                if ($elapsed > 0) {
                    $speed = $downloaded / $elapsed;
                    $remaining = $total - $downloaded;
                    $eta = $remaining / $speed; // seconds remaining
                    $etas[] = round($eta, 4); // Use more precision
                }
            }
        });

        // Add initial delay to ensure elapsed time is measurable
        usleep(1000); // 1ms initial delay

        // Simulate progress
        $totalSize = 10 * 1024 * 1024; // 10MB
        for ($i = 1; $i <= 10; $i++) {
            usleep(1000); // 1ms delay before each callback
            $callback($i * 1024 * 1024, $totalSize);
        }

        $this->assertNotEmpty($etas);
        // ETA should decrease over time (or at least the last should be 0 when complete)
        if (count($etas) > 1) {
            // The last ETA should be 0 since we've downloaded everything
            $this->assertEquals(0, $etas[count($etas) - 1]);
        }
    }
}
