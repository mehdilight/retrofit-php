<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @template T
 */
final class Response implements ResponseInterface
{
    private Psr7Response $psrResponse;

    /**
     * @param T|null $body
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        public readonly int $code,
        public readonly string $message,
        public readonly mixed $body,
        public readonly array $headers = [],
        public readonly ?string $rawBody = null,
    ) {
        $this->psrResponse = new Psr7Response($code, $headers, $rawBody ?? '', $message);
    }

    public function isSuccessful(): bool
    {
        return $this->code >= 200 && $this->code < 300;
    }

    /**
     * @template U
     * @param U $body
     * @return Response<U>
     */
    public static function success(mixed $body, int $code = 200): self
    {
        return new self($code, 'OK', $body);
    }

    /**
     * @return Response<null>
     */
    public static function error(int $code, string $message, ?string $rawBody = null): self
    {
        return new self($code, $message, null, [], $rawBody);
    }

    // PSR-7 ResponseInterface implementation

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $message = $reasonPhrase !== '' ? $reasonPhrase : $this->message;
        return new self($code, $message, $this->body, $this->headers, $this->rawBody);
    }

    public function getReasonPhrase(): string
    {
        return $this->message;
    }

    // PSR-7 MessageInterface implementation

    public function getProtocolVersion(): string
    {
        return $this->psrResponse->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        $newPsrResponse = $this->psrResponse->withProtocolVersion($version);
        $clone = clone $this;
        $clone->psrResponse = $newPsrResponse;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->psrResponse->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrResponse->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->psrResponse->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->psrResponse->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $newHeaders = $this->headers;
        $newHeaders[$name] = $value;

        return new self(
            $this->code,
            $this->message,
            $this->body,
            $newHeaders,
            $this->rawBody,
        );
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $newHeaders = $this->headers;
        if (isset($newHeaders[$name])) {
            $existing = is_array($newHeaders[$name]) ? $newHeaders[$name] : [$newHeaders[$name]];
            $newHeaders[$name] = array_merge($existing, is_array($value) ? $value : [$value]);
        } else {
            $newHeaders[$name] = $value;
        }

        return new self(
            $this->code,
            $this->message,
            $this->body,
            $newHeaders,
            $this->rawBody,
        );
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $newHeaders = $this->headers;
        unset($newHeaders[$name]);

        return new self(
            $this->code,
            $this->message,
            $this->body,
            $newHeaders,
            $this->rawBody,
        );
    }

    public function getBody(): StreamInterface
    {
        return $this->psrResponse->getBody();
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        return new self(
            $this->code,
            $this->message,
            $this->body,
            $this->headers,
            (string) $body,
        );
    }
}
