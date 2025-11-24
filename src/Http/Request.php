<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    private Psr7Request $psrRequest;

    /**
     * @param array<string, string|string[]> $headers
     * @param array<string, mixed> $query
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly mixed $body = null,
    ) {
        // Build URI with query parameters
        $uri = new Uri($url);
        if (!empty($query)) {
            $existingQuery = $uri->getQuery();
            $queryString = http_build_query($query);
            $uri = $uri->withQuery($existingQuery ? $existingQuery . '&' . $queryString : $queryString);
        }

        // Convert body to PSR-7 stream if needed
        // Note: We don't convert the body here as it will be handled by the HTTP client
        // This maintains compatibility with various body types (arrays, objects, etc.)
        $psrBody = is_string($body) ? $body : null;

        $this->psrRequest = new Psr7Request($method, $uri, $headers, $psrBody);
    }

    /**
     * @param array<string, string|string[]> $headers
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            $this->method,
            $this->url,
            array_merge($this->headers, $headers),
            $this->query,
            $this->body,
        );
    }

    /**
     * Add or replace a single header.
     * Supports both PSR-7 (string|string[]) and convenient API (any value).
     */
    public function withHeader($name, $value): self
    {
        $newHeaders = $this->headers;
        $newHeaders[(string) $name] = $value;

        return new self(
            $this->method,
            $this->url,
            $newHeaders,
            $this->query,
            $this->body,
        );
    }

    /**
     * Replace the body with a new value.
     * Supports both PSR-7 StreamInterface and convenient API (mixed values).
     *
     * @param mixed|StreamInterface $body
     */
    public function withBody(mixed $body): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->headers,
            $this->query,
            $body instanceof StreamInterface ? (string) $body : $body,
        );
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQuery(array $query): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->headers,
            array_merge($this->query, $query),
            $this->body,
        );
    }

    // PSR-7 RequestInterface implementation

    public function getRequestTarget(): string
    {
        return $this->psrRequest->getRequestTarget();
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $newPsrRequest = $this->psrRequest->withRequestTarget($requestTarget);
        $clone = clone $this;
        $clone->psrRequest = $newPsrRequest;
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        return new self(
            $method,
            $this->url,
            $this->headers,
            $this->query,
            $this->body,
        );
    }

    public function getUri(): UriInterface
    {
        return $this->psrRequest->getUri();
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $newUrl = (string) $uri;

        $newHeaders = $this->headers;
        if (!$preserveHost && $uri->getHost() !== '') {
            $newHeaders['Host'] = $uri->getHost();
            if ($uri->getPort() !== null) {
                $newHeaders['Host'] .= ':' . $uri->getPort();
            }
        }

        return new self(
            $this->method,
            $newUrl,
            $newHeaders,
            $this->query,
            $this->body,
        );
    }

    // PSR-7 MessageInterface implementation

    public function getProtocolVersion(): string
    {
        return $this->psrRequest->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): RequestInterface
    {
        $newPsrRequest = $this->psrRequest->withProtocolVersion($version);
        $clone = clone $this;
        $clone->psrRequest = $newPsrRequest;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->psrRequest->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->psrRequest->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->psrRequest->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->psrRequest->getHeaderLine($name);
    }

    public function withAddedHeader(string $name, $value): RequestInterface
    {
        $newHeaders = $this->headers;
        if (isset($newHeaders[$name])) {
            $existing = is_array($newHeaders[$name]) ? $newHeaders[$name] : [$newHeaders[$name]];
            $newHeaders[$name] = array_merge($existing, is_array($value) ? $value : [$value]);
        } else {
            $newHeaders[$name] = $value;
        }

        return new self(
            $this->method,
            $this->url,
            $newHeaders,
            $this->query,
            $this->body,
        );
    }

    public function withoutHeader(string $name): RequestInterface
    {
        $newHeaders = $this->headers;
        unset($newHeaders[$name]);

        return new self(
            $this->method,
            $this->url,
            $newHeaders,
            $this->query,
            $this->body,
        );
    }

    public function getBody(): StreamInterface
    {
        return $this->psrRequest->getBody();
    }
}
