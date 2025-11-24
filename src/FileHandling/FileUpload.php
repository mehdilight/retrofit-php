<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\FileHandling;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class FileUpload
{
    private function __construct(
        private readonly StreamInterface $stream,
        private readonly string $filename,
        private readonly string $contentType,
        private readonly ?int $size = null,
    ) {}

    /**
     * Create a FileUpload from a file path.
     *
     * @param string $path Path to the file
     * @param string|null $filename Custom filename (defaults to basename of path)
     * @param string|null $contentType Content type (auto-detected if null)
     * @return self
     * @throws RuntimeException If file doesn't exist or can't be read
     */
    public static function fromPath(
        string $path,
        ?string $filename = null,
        ?string $contentType = null
    ): self {
        if (!file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("File is not readable: {$path}");
        }

        $stream = Utils::streamFor(fopen($path, 'r'));
        $filename = $filename ?? basename($path);
        $contentType = $contentType ?? self::detectContentType($path);
        $size = filesize($path) ?: null;

        return new self($stream, $filename, $contentType, $size);
    }

    /**
     * Create a FileUpload from a string content.
     *
     * @param string $content File content
     * @param string $filename Filename
     * @param string $contentType Content type
     * @return self
     */
    public static function fromString(
        string $content,
        string $filename,
        string $contentType = 'application/octet-stream'
    ): self {
        $stream = Utils::streamFor($content);
        $size = strlen($content);

        return new self($stream, $filename, $contentType, $size);
    }

    /**
     * Create a FileUpload from a PSR-7 stream.
     *
     * @param StreamInterface $stream Stream to upload
     * @param string $filename Filename
     * @param string $contentType Content type
     * @return self
     */
    public static function fromStream(
        StreamInterface $stream,
        string $filename,
        string $contentType = 'application/octet-stream'
    ): self {
        $size = $stream->getSize();

        return new self($stream, $filename, $contentType, $size);
    }

    /**
     * Get the upload stream.
     */
    public function getStream(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Get the filename.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get the content type.
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get the file size in bytes.
     */
    public function getSize(): ?int
    {
        return $this->size ?? $this->stream->getSize();
    }

    /**
     * Detect content type from file extension.
     */
    private static function detectContentType(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'txt' => 'text/plain',
            'html', 'htm' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'tar' => 'application/x-tar',
            'gz', 'gzip' => 'application/gzip',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            default => 'application/octet-stream',
        };
    }
}
