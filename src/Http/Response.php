<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

/**
 * @template T
 */
final class Response
{
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
    ) {}

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
}
