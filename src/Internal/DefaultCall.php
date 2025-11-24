<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

use GuzzleHttp\Promise\PromiseInterface;
use Phpmystic\RetrofitPhp\Cache\CacheInterface;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Contracts\Call;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;

/**
 * @template T
 * @implements Call<T>
 */
final class DefaultCall implements Call
{
    private bool $executed = false;
    private bool $canceled = false;

    /**
     * @param Interceptor[] $interceptors
     */
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly Request $request,
        private readonly ?Converter $requestConverter,
        private readonly ?Converter $responseConverter,
        private readonly array $interceptors = [],
        private readonly ?RetryPolicy $retryPolicy = null,
        private readonly ?CacheInterface $cache = null,
        private readonly ?CachePolicy $cachePolicy = null,
    ) {}

    public function execute(): Response
    {
        if ($this->canceled) {
            throw new \RuntimeException('Call has been canceled.');
        }

        $this->executed = true;

        // Convert request body if converter exists
        $request = $this->request;
        if ($this->requestConverter !== null && $request->body !== null) {
            $convertedBody = $this->requestConverter->convert($request->body);
            $request = $request->withBody($convertedBody);
        }

        // Check cache first
        if ($this->cache !== null && $this->cachePolicy !== null) {
            $cacheKey = $this->cachePolicy->generateKey($request);
            $cachedResponse = $this->cache->get($cacheKey);

            if ($cachedResponse !== null) {
                // Return from cache
                return $this->convertResponse($cachedResponse);
            }
        }

        // Execute with retry logic
        $response = $this->executeWithRetry($request);

        // Store in cache if policy allows
        if ($this->cache !== null && $this->cachePolicy !== null) {
            if ($this->cachePolicy->isCacheable($request, $response)) {
                $cacheKey = $this->cachePolicy->generateKey($request);
                $this->cache->set($cacheKey, $response, $this->cachePolicy->getTtl());
            }
        }

        // Convert response body if converter exists
        return $this->convertResponse($response);
    }

    private function executeWithRetry(Request $request): Response
    {
        $attemptNumber = 0;
        $lastException = null;
        $lastResponse = null;

        while (true) {
            try {
                // Create executor closure for the HTTP client
                $executor = fn(Request $req) => $this->httpClient->execute($req);

                // If we have interceptors, use the chain
                if (!empty($this->interceptors)) {
                    $chain = new InterceptorChain($request, $this->interceptors, $executor);
                    $response = $chain->proceed($request);
                } else {
                    // Execute HTTP request directly
                    $response = $executor($request);
                }

                $lastResponse = $response;
                $lastException = null;

                // Check if we should retry based on response
                if ($this->retryPolicy !== null && $this->retryPolicy->shouldRetry($request, $response, null, $attemptNumber)) {
                    $attemptNumber++;
                    $this->delay($this->retryPolicy->getDelayMs($attemptNumber - 1));
                    continue;
                }

                // If retries are exhausted and response is still not successful, throw exception
                if ($this->retryPolicy !== null && !$response->isSuccessful() && $attemptNumber > 0) {
                    throw new \RuntimeException(
                        "Request failed after {$attemptNumber} retries with status {$response->code}: {$response->message}"
                    );
                }

                // Success or non-retryable response
                return $response;
            } catch (\Throwable $e) {
                $lastException = $e;
                $lastResponse = null;

                // Check if we should retry based on exception
                if ($this->retryPolicy !== null && $this->retryPolicy->shouldRetry($request, null, $e, $attemptNumber)) {
                    $attemptNumber++;
                    $this->delay($this->retryPolicy->getDelayMs($attemptNumber - 1));
                    continue;
                }

                // Non-retryable exception or retries exhausted, rethrow
                throw $e;
            }
        }
    }

    private function convertResponse(Response $response): Response
    {
        if ($this->responseConverter !== null && $response->rawBody !== null) {
            $convertedBody = $this->responseConverter->convert($response->rawBody);
            return new Response(
                $response->code,
                $response->message,
                $convertedBody,
                $response->headers,
                $response->rawBody,
            );
        }

        return $response;
    }

    private function delay(int $milliseconds): void
    {
        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }

    public function executeAsync(): PromiseInterface
    {
        if ($this->canceled) {
            throw new \RuntimeException('Call has been canceled.');
        }

        $this->executed = true;

        // Convert request body if converter exists
        $request = $this->request;
        if ($this->requestConverter !== null && $request->body !== null) {
            $convertedBody = $this->requestConverter->convert($request->body);
            $request = $request->withBody($convertedBody);
        }

        // Execute HTTP request asynchronously
        return $this->httpClient->executeAsync($request)->then(
            function (Response $response) {
                // Convert response body if converter exists
                if ($this->responseConverter !== null && $response->rawBody !== null) {
                    $convertedBody = $this->responseConverter->convert($response->rawBody);
                    return new Response(
                        $response->code,
                        $response->message,
                        $convertedBody,
                        $response->headers,
                        $response->rawBody,
                    );
                }
                return $response;
            }
        );
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function cancel(): void
    {
        $this->canceled = true;
    }

    public function isCanceled(): bool
    {
        return $this->canceled;
    }

    public function clone(): Call
    {
        return new self(
            $this->httpClient,
            $this->request,
            $this->requestConverter,
            $this->responseConverter,
            $this->interceptors,
            $this->retryPolicy,
            $this->cache,
            $this->cachePolicy,
        );
    }

    public function request(): Request
    {
        return $this->request;
    }
}
