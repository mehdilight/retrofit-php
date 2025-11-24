<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

use GuzzleHttp\Promise\PromiseInterface;
use Phpmystic\RetrofitPhp\Contracts\Call;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

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
        );
    }

    public function request(): Request
    {
        return $this->request;
    }
}
