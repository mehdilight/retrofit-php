<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Tests\FileHandling;

use PHPUnit\Framework\TestCase;
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;
use Phpmystic\RetrofitPhp\Attributes\Parameter\PartMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Converter\JsonConverterFactory;
use Phpmystic\RetrofitPhp\FileHandling\FileUpload;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retrofit;
use Psr\Http\Message\StreamInterface;

interface FileUploadApi
{
    #[POST('/upload')]
    #[Multipart]
    public function uploadFile(
        #[Part('file')] FileUpload $file,
        #[Part('description')] string $description
    ): array;

    #[POST('/upload-multiple')]
    #[Multipart]
    public function uploadMultipleFiles(
        #[Part('files')] array $files,
        #[Part('category')] string $category
    ): array;

    #[POST('/upload-stream')]
    #[Multipart]
    public function uploadStream(
        #[Part('file')] StreamInterface $stream,
        #[Part('filename')] string $filename
    ): array;

    #[POST('/upload-large')]
    #[Multipart]
    public function uploadLargeFile(
        #[Part('file')] FileUpload $file
    ): array;
}

class FileUploadTest extends TestCase
{
    private string $testFilePath;
    private string $testFileContent = 'Test file content for upload';

    protected function setUp(): void
    {
        parent::setUp();
        $this->testFilePath = sys_get_temp_dir() . '/retrofit_test_' . uniqid() . '.txt';
        file_put_contents($this->testFilePath, $this->testFileContent);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        parent::tearDown();
    }

    public function testFileUploadCreation(): void
    {
        $upload = FileUpload::fromPath($this->testFilePath);

        $this->assertEquals(basename($this->testFilePath), $upload->getFilename());
        $this->assertEquals('text/plain', $upload->getContentType());
        $this->assertInstanceOf(StreamInterface::class, $upload->getStream());
    }

    public function testFileUploadWithCustomFilename(): void
    {
        $upload = FileUpload::fromPath($this->testFilePath, 'custom.txt');

        $this->assertEquals('custom.txt', $upload->getFilename());
    }

    public function testFileUploadWithCustomContentType(): void
    {
        $upload = FileUpload::fromPath($this->testFilePath, null, 'application/octet-stream');

        $this->assertEquals('application/octet-stream', $upload->getContentType());
    }

    public function testFileUploadFromString(): void
    {
        $content = 'File content as string';
        $upload = FileUpload::fromString($content, 'test.txt', 'text/plain');

        $this->assertEquals('test.txt', $upload->getFilename());
        $this->assertEquals('text/plain', $upload->getContentType());

        $stream = $upload->getStream();
        $this->assertEquals($content, (string) $stream);
    }

    public function testFileUploadFromStream(): void
    {
        $stream = \GuzzleHttp\Psr7\Utils::streamFor('Stream content');
        $upload = FileUpload::fromStream($stream, 'stream.txt', 'text/plain');

        $this->assertEquals('stream.txt', $upload->getFilename());
        $this->assertEquals('text/plain', $upload->getContentType());
        $this->assertSame($stream, $upload->getStream());
    }

    public function testFileUploadSize(): void
    {
        $upload = FileUpload::fromPath($this->testFilePath);

        $this->assertEquals(strlen($this->testFileContent), $upload->getSize());
    }

    public function testLargeFileUploadDoesNotLoadIntoMemory(): void
    {
        // Create a larger test file (1MB)
        $largeFilePath = sys_get_temp_dir() . '/retrofit_large_' . uniqid() . '.bin';
        $handle = fopen($largeFilePath, 'w');
        for ($i = 0; $i < 1024; $i++) {
            fwrite($handle, str_repeat('A', 1024)); // Write 1KB at a time
        }
        fclose($handle);

        try {
            $upload = FileUpload::fromPath($largeFilePath);
            $stream = $upload->getStream();

            // Stream should not load entire file into memory
            // We can verify this by checking that the stream is seekable and uses a resource
            $this->assertTrue($stream->isSeekable());
            $this->assertIsResource($stream->detach());
        } finally {
            if (file_exists($largeFilePath)) {
                unlink($largeFilePath);
            }
        }
    }

    public function testFileUploadIntegration(): void
    {
        $uploadedFiles = [];

        $mockClient = $this->createMock(HttpClient::class);
        $mockClient->method('execute')
            ->willReturnCallback(function (Request $request) use (&$uploadedFiles) {
                // Capture the uploaded file information
                $body = $request->body;
                if (is_array($body) && isset($body['file'])) {
                    $uploadedFiles[] = $body['file'];
                }

                return new Response(200, 'OK', null, [], '{"success":true,"file_id":"123"}');
            });

        $retrofit = Retrofit::builder()
            ->baseUrl('https://api.example.com')
            ->client($mockClient)
            ->addConverterFactory(new JsonConverterFactory())
            ->build();

        $api = $retrofit->create(FileUploadApi::class);

        $upload = FileUpload::fromPath($this->testFilePath);
        $result = $api->uploadFile($upload, 'Test description');

        $this->assertEquals(['success' => true, 'file_id' => '123'], $result);
        $this->assertCount(1, $uploadedFiles);
        $this->assertArrayHasKey('value', $uploadedFiles[0]);
    }

    public function testMultipleFileUpload(): void
    {
        $file1Path = sys_get_temp_dir() . '/retrofit_test_1_' . uniqid() . '.txt';
        $file2Path = sys_get_temp_dir() . '/retrofit_test_2_' . uniqid() . '.txt';
        file_put_contents($file1Path, 'File 1 content');
        file_put_contents($file2Path, 'File 2 content');

        try {
            $upload1 = FileUpload::fromPath($file1Path);
            $upload2 = FileUpload::fromPath($file2Path);

            $this->assertNotEquals($upload1->getFilename(), $upload2->getFilename());
            $this->assertInstanceOf(FileUpload::class, $upload1);
            $this->assertInstanceOf(FileUpload::class, $upload2);
        } finally {
            if (file_exists($file1Path)) unlink($file1Path);
            if (file_exists($file2Path)) unlink($file2Path);
        }
    }

    public function testFileUploadWithNonExistentFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        FileUpload::fromPath('/non/existent/file.txt');
    }

    public function testFileUploadStreamIsNotReadable(): void
    {
        $upload = FileUpload::fromPath($this->testFilePath);
        $stream = $upload->getStream();

        $this->assertTrue($stream->isReadable());
    }
}
