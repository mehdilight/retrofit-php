<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

final class Request
{
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
    ) {}

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

    public function withBody(mixed $body): self
    {
        return new self(
            $this->method,
            $this->url,
            $this->headers,
            $this->query,
            $body,
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
}
