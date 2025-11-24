# File Handling

Retrofit PHP provides efficient file upload and download capabilities with streaming support and progress tracking.

## File Uploads

Upload files using multipart form data with the `FileUpload` class.

### Basic File Upload

```php
use Phpmystic\RetrofitPhp\Attributes\Http\POST;
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;
use Phpmystic\RetrofitPhp\FileHandling\FileUpload;

interface FileApi
{
    #[POST('/upload')]
    #[Multipart]
    public function uploadFile(
        #[Part('file')] FileUpload $file,
        #[Part('description')] string $description
    ): array;
}

// Usage
$api = $retrofit->create(FileApi::class);

$file = FileUpload::fromPath('/path/to/document.pdf');
$result = $api->uploadFile($file, 'Important document');
```

### Upload Methods

#### From File Path

Upload a file from the filesystem.

```php
$file = FileUpload::fromPath('/path/to/image.jpg');
$result = $api->uploadFile($file, 'Profile picture');
```

#### From String Content

Upload content directly from a string.

```php
$content = 'This is the file content';
$file = FileUpload::fromString($content, 'data.txt', 'text/plain');
$result = $api->uploadFile($file, 'Text data');
```

#### From PSR-7 Stream

Upload from a PSR-7 StreamInterface.

```php
use GuzzleHttp\Psr7\Utils;

$stream = Utils::streamFor(fopen('/path/to/video.mp4', 'r'));
$file = FileUpload::fromStream($stream, 'video.mp4', 'video/mp4');
$result = $api->uploadFile($file, 'Video upload');
```

### Multiple Files

Upload multiple files in a single request.

```php
interface FileApi
{
    #[POST('/upload-multiple')]
    #[Multipart]
    public function uploadMultiple(
        #[Part('files')] array $files,
        #[Part('description')] string $description
    ): array;
}

// Usage
$files = [
    FileUpload::fromPath('/path/to/file1.pdf'),
    FileUpload::fromPath('/path/to/file2.pdf'),
    FileUpload::fromPath('/path/to/file3.pdf'),
];

$result = $api->uploadMultiple($files, 'Batch upload');
```

### Mixed Form Data

Combine files with other form fields.

```php
interface DocumentApi
{
    #[POST('/documents')]
    #[Multipart]
    public function createDocument(
        #[Part('file')] FileUpload $file,
        #[Part('title')] string $title,
        #[Part('author')] string $author,
        #[Part('tags')] array $tags
    ): array;
}

// Usage
$file = FileUpload::fromPath('/path/to/report.pdf');
$result = $api->createDocument(
    $file,
    'Annual Report 2024',
    'John Doe',
    ['finance', 'annual', '2024']
);
```

### Content Type Detection

FileUpload automatically detects content types.

```php
// Automatic detection based on file extension
$pdf = FileUpload::fromPath('/path/to/file.pdf');
// Content-Type: application/pdf

$image = FileUpload::fromPath('/path/to/photo.jpg');
// Content-Type: image/jpeg

$json = FileUpload::fromPath('/path/to/data.json');
// Content-Type: application/json

// Manual content type
$file = FileUpload::fromPath('/path/to/file.dat', 'application/octet-stream');
```

### Custom Filename

Override the filename sent to the server.

```php
$file = FileUpload::fromPath('/path/to/local-file.pdf');
$file = $file->withFilename('server-filename.pdf');
$result = $api->uploadFile($file, 'Renamed file');
```

## File Downloads

Download files efficiently using streaming to avoid memory issues.

### Streaming Downloads

Use the `#[Streaming]` attribute to download files as streams.

```php
use Phpmystic\RetrofitPhp\Attributes\Http\GET;
use Phpmystic\RetrofitPhp\Attributes\Streaming;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Psr\Http\Message\StreamInterface;

interface DownloadApi
{
    #[GET('/files/{id}/download')]
    #[Streaming]
    public function downloadFile(#[Path('id')] string $fileId): StreamInterface;
}

// Usage
$api = $retrofit->create(DownloadApi::class);
$stream = $api->downloadFile('12345');

// Save to file
file_put_contents('/tmp/download.zip', (string) $stream);
```

### Chunked Reading

Read large files in chunks to manage memory.

```php
$stream = $api->downloadFile('large-file-id');

// Rewind to start
$stream->rewind();

// Read in 8KB chunks
$output = fopen('/tmp/output.bin', 'w');
while (!$stream->eof()) {
    $chunk = $stream->read(8192);
    fwrite($output, $chunk);
}
fclose($output);
```

### Direct File Writing

Write stream directly to disk.

```php
$stream = $api->downloadFile('file-123');

// Open output file
$output = fopen('/tmp/download.pdf', 'w');

// Write stream contents
$stream->rewind();
stream_copy_to_stream($stream->detach(), $output);

fclose($output);
```

### Stream Metadata

Access stream information.

```php
$stream = $api->downloadFile('file-123');

$size = $stream->getSize();
echo "File size: {$size} bytes\n";

$stream->seek(0); // Seek to beginning
$stream->seek(100); // Seek to position 100
$stream->seek(0, SEEK_END); // Seek to end

$position = $stream->tell();
echo "Current position: {$position}\n";
```

## Progress Tracking

Monitor upload and download progress with callbacks.

### Basic Progress Callback

```php
use Phpmystic\RetrofitPhp\FileHandling\ProgressCallback;

$progress = new ProgressCallback(function ($bytesTransferred, $totalBytes) {
    if ($totalBytes > 0) {
        $percentage = ($bytesTransferred / $totalBytes) * 100;
        echo sprintf(
            "Progress: %.2f%% (%d / %d bytes)\r",
            $percentage,
            $bytesTransferred,
            $totalBytes
        );
    }
});

// Configure client with progress tracking
$client = GuzzleHttpClient::create([
    'progress' => $progress->getCallback()
]);

$retrofit = Retrofit::builder()
    ->baseUrl('https://api.example.com')
    ->client($client)
    ->build();
```

### Advanced Progress Tracking

```php
class AdvancedProgress
{
    private float $startTime;
    private int $lastBytes = 0;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function callback(int $bytesTransferred, int $totalBytes): void
    {
        if ($totalBytes === 0) {
            return;
        }

        $elapsed = microtime(true) - $this->startTime;
        $percentage = ($bytesTransferred / $totalBytes) * 100;

        // Calculate speed
        $bytesPerSecond = $elapsed > 0 ? $bytesTransferred / $elapsed : 0;
        $speed = $this->formatBytes($bytesPerSecond) . '/s';

        // Estimate remaining time
        $remainingBytes = $totalBytes - $bytesTransferred;
        $eta = $bytesPerSecond > 0 ? $remainingBytes / $bytesPerSecond : 0;

        echo sprintf(
            "Progress: %.1f%% | Speed: %s | ETA: %ds | %s / %s\r",
            $percentage,
            $speed,
            (int) $eta,
            $this->formatBytes($bytesTransferred),
            $this->formatBytes($totalBytes)
        );
    }

    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Usage
$progress = new AdvancedProgress();
$client = GuzzleHttpClient::create([
    'progress' => [$progress, 'callback']
]);
```

### Progress with UI Integration

```php
class ProgressBar
{
    private int $width;

    public function __construct(int $width = 50)
    {
        $this->width = $width;
    }

    public function callback(int $transferred, int $total): void
    {
        if ($total === 0) {
            return;
        }

        $percentage = ($transferred / $total) * 100;
        $filled = (int) (($transferred / $total) * $this->width);
        $empty = $this->width - $filled;

        $bar = str_repeat('=', $filled) . str_repeat(' ', $empty);

        echo sprintf(
            "\r[%s] %.1f%% (%d / %d bytes)",
            $bar,
            $percentage,
            $transferred,
            $total
        );

        if ($transferred === $total) {
            echo "\n";
        }
    }
}

// Usage
$progressBar = new ProgressBar(50);
$client = GuzzleHttpClient::create([
    'progress' => [$progressBar, 'callback']
]);
```

## Best Practices

### 1. Use Streaming for Large Files

Always use streaming for files larger than available memory.

```php
// Good: Streaming for large file
#[GET('/files/{id}')]
#[Streaming]
public function downloadLargeFile(string $id): StreamInterface;

// Bad: Loading entire file into memory
#[GET('/files/{id}')]
public function downloadLargeFile(string $id): string;
```

### 2. Set Appropriate Timeouts

Use longer timeouts for file operations.

```php
$client = GuzzleHttpClient::create([
    'timeout' => 900,  // 15 minutes for large files
]);
```

### 3. Validate File Types

Validate uploaded files before processing.

```php
public function uploadFile(FileUpload $file): array
{
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    if (!in_array($file->getContentType(), $allowedTypes)) {
        throw new \InvalidArgumentException('Invalid file type');
    }

    return $this->api->uploadFile($file);
}
```

### 4. Handle Upload Errors

Implement proper error handling for uploads.

```php
try {
    $file = FileUpload::fromPath('/path/to/file.pdf');
    $result = $api->uploadFile($file, 'Document');
} catch (\Exception $e) {
    error_log("Upload failed: {$e->getMessage()}");
    throw new UploadException('Failed to upload file', 0, $e);
}
```

### 5. Implement Resume Logic

For very large files, implement resume capability.

```php
interface FileApi
{
    #[POST('/files/{id}/upload')]
    #[Multipart]
    public function uploadChunk(
        #[Path('id')] string $fileId,
        #[Part('chunk')] FileUpload $chunk,
        #[Part('chunk_number')] int $chunkNumber,
        #[Part('total_chunks')] int $totalChunks
    ): array;
}
```

## Complete Examples

### Image Upload with Validation

```php
interface ImageApi
{
    #[POST('/images')]
    #[Multipart]
    public function uploadImage(
        #[Part('image')] FileUpload $image,
        #[Part('alt')] string $altText
    ): array;
}

class ImageService
{
    public function __construct(private ImageApi $api) {}

    public function uploadImage(string $path, string $altText): array
    {
        // Validate file exists
        if (!file_exists($path)) {
            throw new \InvalidArgumentException('File not found');
        }

        // Validate file type
        $mimeType = mime_content_type($path);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid image type');
        }

        // Validate file size (max 5MB)
        if (filesize($path) > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('File too large (max 5MB)');
        }

        $file = FileUpload::fromPath($path);
        return $this->api->uploadImage($file, $altText);
    }
}
```

### Document Download with Progress

```php
interface DocumentApi
{
    #[GET('/documents/{id}/download')]
    #[Streaming]
    public function download(#[Path('id')] string $id): StreamInterface;
}

class DocumentService
{
    public function downloadDocument(string $id, string $outputPath): void
    {
        $stream = $this->api->download($id);

        $output = fopen($outputPath, 'w');
        $totalBytes = $stream->getSize() ?? 0;
        $transferred = 0;

        $stream->rewind();
        while (!$stream->eof()) {
            $chunk = $stream->read(8192);
            fwrite($output, $chunk);

            $transferred += strlen($chunk);

            // Update progress
            if ($totalBytes > 0) {
                $percentage = ($transferred / $totalBytes) * 100;
                echo sprintf("Downloaded: %.1f%%\r", $percentage);
            }
        }

        fclose($output);
        echo "\nDownload complete!\n";
    }
}
```

## Limitations

- Maximum file size depends on PHP configuration (memory_limit, upload_max_filesize)
- Progress tracking only works with Guzzle HTTP client
- Streaming requires server support for chunked transfer
- Cannot pause/resume uploads natively (requires custom implementation)

## See Also

- [Request Body](request-body.md) - Learn about other body types
- [Client Configuration](client-configuration.md) - Configure timeouts for files
- [Timeouts](timeouts.md) - Set appropriate timeouts for file operations
