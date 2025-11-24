<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

use GuzzleHttp\Promise\PromiseInterface;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

final class Psr18HttpClient implements HttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    /**
     * Create a new Psr18HttpClient with Nyholm PSR-17 factories.
     */
    public static function create(ClientInterface $client): self
    {
        $psr17Factory = new Psr17Factory();
        return new self($client, $psr17Factory, $psr17Factory);
    }

    public function execute(Request $request): Response
    {
        // Create PSR-7 request
        $psrRequest = $this->requestFactory->createRequest(
            $request->method,
            $this->buildUrl($request)
        );

        // Add headers
        foreach ($request->headers as $name => $value) {
            $psrRequest = $psrRequest->withHeader($name, $value);
        }

        // Add body
        if ($request->body !== null) {
            if (is_string($request->body)) {
                $stream = $this->streamFactory->createStream($request->body);
                $psrRequest = $psrRequest->withBody($stream);
            }
        }

        // Send request
        $psrResponse = $this->client->sendRequest($psrRequest);

        return $this->buildResponse($psrResponse);
    }

    public function executeAsync(Request $request): PromiseInterface
    {
        throw new \RuntimeException('PSR-18 does not support async requests. Use GuzzleHttpClient for async support.');
    }

    private function buildUrl(Request $request): string
    {
        $url = $request->url;

        if (!empty($request->query)) {
            $queryString = http_build_query($request->query);
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $queryString;
        }

        return $url;
    }

    private function buildResponse(PsrResponseInterface $psrResponse): Response
    {
        $headers = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        $rawBody = $psrResponse->getBody()->getContents();

        return new Response(
            code: $psrResponse->getStatusCode(),
            message: $psrResponse->getReasonPhrase(),
            body: null,
            headers: $headers,
            rawBody: $rawBody,
        );
    }
}
