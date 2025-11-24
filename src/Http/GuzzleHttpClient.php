<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\FileHandling\FileUpload;
use Phpmystic\RetrofitPhp\FileHandling\ProgressCallback;

final class GuzzleHttpClient implements HttpClient
{
    public function __construct(
        private readonly ClientInterface $client,
    ) {}

    /**
     * Create a new GuzzleHttpClient with default or custom options.
     *
     * @param array<string, mixed> $options Guzzle client options
     */
    public static function create(array $options = []): self
    {
        return new self(new Client($options));
    }

    public function execute(Request $request): Response
    {
        $options = $this->buildOptions($request);

        try {
            $guzzleResponse = $this->client->request(
                $request->method,
                $request->url,
                $options,
            );

            return $this->buildResponse($guzzleResponse);
        } catch (GuzzleException $e) {
            if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                return $this->buildResponse($e->getResponse());
            }

            throw $e;
        }
    }

    public function executeAsync(Request $request): PromiseInterface
    {
        $options = $this->buildOptions($request);

        return $this->client->requestAsync(
            $request->method,
            $request->url,
            $options,
        )->then(
            fn(\Psr\Http\Message\ResponseInterface $response) => $this->buildResponse($response),
            function (\Throwable $e) {
                if (method_exists($e, 'getResponse') && $e->getResponse() !== null) {
                    return $this->buildResponse($e->getResponse());
                }
                throw $e;
            }
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildOptions(Request $request): array
    {
        $options = [
            'http_errors' => false,
        ];

        // Headers
        if (!empty($request->headers)) {
            $options['headers'] = $request->headers;
        }

        // Query parameters
        if (!empty($request->query)) {
            $options['query'] = $request->query;
        }

        // Body
        if ($request->body !== null) {
            $contentType = $request->headers['Content-Type'] ?? '';

            if (is_array($request->body) && str_contains($contentType, 'application/x-www-form-urlencoded')) {
                $options['form_params'] = $request->body;
            } elseif (is_array($request->body) && $this->isMultipartRequest($request->body)) {
                // Handle multipart with FileUpload objects
                $options['multipart'] = $this->buildMultipartBody($request->body);
            } elseif (is_string($request->body)) {
                $options['body'] = $request->body;
                // Add JSON content-type if body looks like JSON and no content-type set
                if (!isset($options['headers']['Content-Type']) && $this->looksLikeJson($request->body)) {
                    $options['headers']['Content-Type'] = 'application/json';
                }
            } else {
                $options['json'] = $request->body;
            }
        }

        return $options;
    }

    /**
     * Check if request body contains multipart data (FileUpload objects or streams).
     *
     * @param array<string, mixed> $body
     */
    private function isMultipartRequest(array $body): bool
    {
        foreach ($body as $value) {
            if (is_array($value) && isset($value['value'])) {
                $val = $value['value'];
                if ($val instanceof FileUpload || $val instanceof \Psr\Http\Message\StreamInterface) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Build Guzzle multipart array from request body.
     *
     * @param array<string, mixed> $body
     * @return array<int, array<string, mixed>>
     */
    private function buildMultipartBody(array $body): array
    {
        $multipart = [];

        foreach ($body as $name => $item) {
            if (is_array($item) && isset($item['value'])) {
                $value = $item['value'];
                $contentType = $item['contentType'] ?? null;

                if ($value instanceof FileUpload) {
                    $multipart[] = [
                        'name' => $name,
                        'contents' => $value->getStream(),
                        'filename' => $value->getFilename(),
                        'headers' => $contentType ? ['Content-Type' => $contentType] :
                                    ($value->getContentType() ? ['Content-Type' => $value->getContentType()] : []),
                    ];
                } elseif ($value instanceof \Psr\Http\Message\StreamInterface) {
                    $multipart[] = [
                        'name' => $name,
                        'contents' => $value,
                        'filename' => $item['filename'] ?? 'file',
                        'headers' => $contentType ? ['Content-Type' => $contentType] : [],
                    ];
                } else {
                    // Regular form field
                    $multipart[] = [
                        'name' => $name,
                        'contents' => (string) $value,
                    ];
                }
            }
        }

        return $multipart;
    }

    private function looksLikeJson(string $body): bool
    {
        $trimmed = trim($body);
        return (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))
            || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'));
    }

    private function buildResponse(\Psr\Http\Message\ResponseInterface $guzzleResponse): Response
    {
        $headers = [];
        foreach ($guzzleResponse->getHeaders() as $name => $values) {
            $headers[$name] = count($values) === 1 ? $values[0] : $values;
        }

        $rawBody = $guzzleResponse->getBody()->getContents();

        return new Response(
            code: $guzzleResponse->getStatusCode(),
            message: $guzzleResponse->getReasonPhrase(),
            body: null,
            headers: $headers,
            rawBody: $rawBody,
        );
    }
}
