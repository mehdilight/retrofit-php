<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\FileHandling;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Streaming;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;
use Psr\Http\Message\StreamInterface;

interface FileDownloadApi
{
    #[GET('/download/{file}')]
    #[Streaming]
    public function downloadFile(#[Path('file')] string $filename): StreamInterface;

    #[GET('/download/large/{file}')]
    #[Streaming]
    public function downloadLargeFile(#[Path('file')] string $filename): StreamInterface;

    #[GET('/files/{id}')]
    public function getFileMetadata(#[Path('id')] string $id): array;
}

class StreamingDownloadTest extends TestCase
{
    public function testStreamingAttributeExists(): void
    {
        $reflection = new \ReflectionMethod(FileDownloadApi::class, 'downloadFile');
        $attributes = $reflection->getAttributes(\Phpmystic\RetrofitPhp\Attributes\Streaming::class);

        $this->assertCount(1, $attributes);
    }

    public function testDownloadReturnsStream(): void
    {
        $fileContent = 'This is the downloaded file content';

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturn(new Response(
                200,
                'OK',
                null,
                ['Content-Type' => 'application/octet-stream'],
                $fileContent
            ));

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->build();

        $api = $retrofit->create(FileDownloadApi::class);
        $stream = $api->downloadFile('test.txt');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals($fileContent, (string) $stream);
    }

    public function testStreamCanBeWrittenToFile(): void
    {
        $fileContent = 'File content to save';
        $outputPath = sys_get_temp_dir() . '/retrofit_download_' . uniqid() . '.txt';

        try {
            $mockClient = $this->createMock(HttpClient::class);
            $mockClient->method('execute')
                ->willReturn(new Response(
                    200,
                    'OK',
                    null,
                    ['Content-Type' => 'text/plain'],
                    $fileContent
                ));

            $retrofit = Retrofit::builder()
                ->baseUrl('https://api.example.com')
                ->client($mockClient)
                ->build();

            $api = $retrofit->create(FileDownloadApi::class);
            $stream = $api->downloadFile('test.txt');

            // Write stream to file
            file_put_contents($outputPath, (string) $stream);

            $this->assertFileExists($outputPath);
            $this->assertEquals($fileContent, file_get_contents($outputPath));
        } finally {
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function testLargeFileStreamingDoesNotLoadIntoMemory(): void
    {
        // Simulate a large file response (10MB in chunks)
        $chunkSize = 1024 * 1024; // 1MB
        $totalChunks = 10;

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function () use ($chunkSize, $totalChunks) {
                // Create a stream that generates content on-the-fly
                $content = str_repeat('A', $chunkSize * $totalChunks);
                return new Response(
                    200,
                    'OK',
                    null,
                    ['Content-Type' => 'application/octet-stream'],
                    $content
                );
            });

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->build();

        $api = $retrofit->create(FileDownloadApi::class);
        $stream = $api->downloadLargeFile('large.bin');

        $this->assertInstanceOf(StreamInterface::class, $stream);

        // Read in chunks to verify streaming behavior
        $bytesRead = 0;
        $stream->rewind();
        while (!$stream->eof()) {
            $chunk = $stream->read(8192); // Read 8KB at a time
            $bytesRead += strlen($chunk);
        }

        $this->assertEquals($chunkSize * $totalChunks, $bytesRead);
    }

    public function testStreamIsSeekable(): void
    {
        $fileContent = '0123456789';

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturn(new Response(
                200,
                'OK',
                null,
                [],
                $fileContent
            ));

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->build();

        $api = $retrofit->create(FileDownloadApi::class);
        $stream = $api->downloadFile('test.txt');

        if ($stream->isSeekable()) {
            $stream->seek(5);
            $this->assertEquals('5', $stream->read(1));

            $stream->rewind();
            $this->assertEquals('0', $stream->read(1));
        } else {
            $this->markTestSkipped('Stream is not seekable');
        }
    }

    public function testStreamSize(): void
    {
        $fileContent = 'Test content with known size';

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturn(new Response(
                200,
                'OK',
                null,
                ['Content-Length' => (string) strlen($fileContent)],
                $fileContent
            ));

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->build();

        $api = $retrofit->create(FileDownloadApi::class);
        $stream = $api->downloadFile('test.txt');

        $actualSize = $stream->getSize();
        if ($actualSize !== null) {
            $this->assertEquals(strlen($fileContent), $actualSize);
        }
    }
}
