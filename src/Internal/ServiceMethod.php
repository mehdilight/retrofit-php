<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Phpmystic\RetrofitPhp\Attributes\FormUrlEncoded;
use Phpmystic\RetrofitPhp\Attributes\Header;
use Phpmystic\RetrofitPhp\Attributes\HeaderMap;
use Phpmystic\RetrofitPhp\Attributes\Headers;
use Phpmystic\RetrofitPhp\Attributes\Http\HttpMethod;
use Phpmystic\RetrofitPhp\Attributes\Multipart;
use Phpmystic\RetrofitPhp\Attributes\Streaming;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Body;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Field;
use Phpmystic\RetrofitPhp\Attributes\Parameter\FieldMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Part;
use Phpmystic\RetrofitPhp\Attributes\Parameter\PartMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Path;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Query;
use Phpmystic\RetrofitPhp\Attributes\Parameter\QueryMap;
use Phpmystic\RetrofitPhp\Attributes\Parameter\Url;
use Phpmystic\RetrofitPhp\Attributes\ResponseType;
use Phpmystic\RetrofitPhp\Contracts\Converter;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\FileHandling\FileUpload;
use Phpmystic\RetrofitPhp\Http\Request;
use RuntimeException;

final class ServiceMethod
{
    private string $httpMethod;
    private string $relativePath;
    private bool $isFormEncoded = false;
    private bool $isMultipart = false;
    private bool $isStreaming = false;

    /** @var array<string, string> */
    private array $staticHeaders = [];

    /** @var array<int, ParameterHandler> */
    private array $parameterHandlers = [];

    private ?ReflectionNamedType $returnType = null;
    private ?ResponseType $responseType = null;

    public function __construct(
        private readonly ReflectionMethod $method,
        private readonly string $baseUrl,
        /** @var ConverterFactory[] */
        private readonly array $converterFactories,
    ) {
        $this->parseMethodAttributes();
        $this->parseParameterAttributes();
        $this->returnType = $method->getReturnType() instanceof ReflectionNamedType
            ? $method->getReturnType()
            : null;

        // Parse ResponseType attribute
        $responseTypeAttr = $this->findAttribute($this->method, ResponseType::class);
        if ($responseTypeAttr !== null) {
            $this->responseType = $responseTypeAttr->newInstance();
        }
    }

    private function parseMethodAttributes(): void
    {
        // Parse HTTP method
        $httpMethodAttr = $this->findAttribute($this->method, HttpMethod::class);
        if ($httpMethodAttr === null) {
            throw new RuntimeException(
                "HTTP method annotation is required (e.g., #[GET], #[POST]) on {$this->method->getName()}"
            );
        }
        /** @var HttpMethod $httpMethod */
        $httpMethod = $httpMethodAttr->newInstance();
        $this->httpMethod = $httpMethod->method();
        $this->relativePath = $httpMethod->path;

        // Parse Headers
        $headersAttr = $this->findAttribute($this->method, Headers::class);
        if ($headersAttr !== null) {
            $this->staticHeaders = $headersAttr->newInstance()->headers;
        }

        // Parse encoding
        if ($this->findAttribute($this->method, FormUrlEncoded::class) !== null) {
            $this->isFormEncoded = true;
        }
        if ($this->findAttribute($this->method, Multipart::class) !== null) {
            $this->isMultipart = true;
        }
        if ($this->findAttribute($this->method, Streaming::class) !== null) {
            $this->isStreaming = true;
        }
    }

    private function parseParameterAttributes(): void
    {
        foreach ($this->method->getParameters() as $index => $param) {
            $handler = $this->createParameterHandler($param);
            $this->parameterHandlers[$index] = $handler;
        }
    }

    private function createParameterHandler(ReflectionParameter $param): ParameterHandler
    {
        $name = $param->getName();

        // Path
        if ($attr = $this->findAttribute($param, Path::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::Path,
                $instance->name ?? $name,
                encoded: $instance->encoded,
            );
        }

        // Query
        if ($attr = $this->findAttribute($param, Query::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::Query,
                $instance->name ?? $name,
                encoded: $instance->encoded,
            );
        }

        // QueryMap
        if ($this->findAttribute($param, QueryMap::class) !== null) {
            return new ParameterHandler(ParameterType::QueryMap, $name);
        }

        // Body
        if ($this->findAttribute($param, Body::class) !== null) {
            return new ParameterHandler(ParameterType::Body, $name);
        }

        // Field
        if ($attr = $this->findAttribute($param, Field::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::Field,
                $instance->name ?? $name,
                encoded: $instance->encoded,
            );
        }

        // FieldMap
        if ($this->findAttribute($param, FieldMap::class) !== null) {
            return new ParameterHandler(ParameterType::FieldMap, $name);
        }

        // Part
        if ($attr = $this->findAttribute($param, Part::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::Part,
                $instance->name ?? $name,
                contentType: $instance->contentType,
            );
        }

        // PartMap
        if ($attr = $this->findAttribute($param, PartMap::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::PartMap,
                $name,
                contentType: $instance->contentType,
            );
        }

        // Header
        if ($attr = $this->findAttribute($param, Header::class)) {
            $instance = $attr->newInstance();
            return new ParameterHandler(
                ParameterType::Header,
                $instance->name ?? $name,
            );
        }

        // HeaderMap
        if ($this->findAttribute($param, HeaderMap::class) !== null) {
            return new ParameterHandler(ParameterType::HeaderMap, $name);
        }

        // Url
        if ($this->findAttribute($param, Url::class) !== null) {
            return new ParameterHandler(ParameterType::Url, $name);
        }

        // Default: treat as query parameter
        return new ParameterHandler(ParameterType::Query, $name);
    }

    /**
     * @template T of object
     * @param ReflectionMethod|ReflectionParameter $target
     * @param class-string<T> $attributeClass
     * @return ReflectionAttribute<T>|null
     */
    private function findAttribute(
        ReflectionMethod|ReflectionParameter $target,
        string $attributeClass,
    ): ?ReflectionAttribute {
        $attrs = $target->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        return $attrs[0] ?? null;
    }

    /**
     * @param array<int, mixed> $args
     */
    public function buildRequest(array $args): Request
    {
        $url = $this->relativePath;
        $query = [];
        $headers = $this->staticHeaders;
        $body = null;
        $fields = [];
        $parts = [];
        $dynamicUrl = null;

        foreach ($this->parameterHandlers as $index => $handler) {
            $value = $args[$index] ?? null;
            if ($value === null) {
                continue;
            }

            match ($handler->type) {
                ParameterType::Path => $url = $this->substitutePath($url, $handler->name, $value, $handler->encoded),
                ParameterType::Query => $query[$handler->name] = $handler->encoded ? $value : $value,
                ParameterType::QueryMap => $query = array_merge($query, (array) $value),
                ParameterType::Body => $body = $value,
                ParameterType::Field => $fields[$handler->name] = $value,
                ParameterType::FieldMap => $fields = array_merge($fields, (array) $value),
                ParameterType::Part => $parts[$handler->name] = ['value' => $value, 'contentType' => $handler->contentType],
                ParameterType::PartMap => $parts = array_merge($parts, (array) $value),
                ParameterType::Header => $headers[$handler->name] = (string) $value,
                ParameterType::HeaderMap => $headers = array_merge($headers, (array) $value),
                ParameterType::Url => $dynamicUrl = $value,
            };
        }

        // Build final URL
        $finalUrl = $dynamicUrl ?? ($this->baseUrl . '/' . ltrim($url, '/'));

        // Handle form-encoded body
        if ($this->isFormEncoded && !empty($fields)) {
            $body = $fields;
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        // Handle multipart body
        if ($this->isMultipart && !empty($parts)) {
            $body = $parts;
            // Content-Type will be set by the HTTP client with boundary
        }

        return new Request(
            method: $this->httpMethod,
            url: $finalUrl,
            headers: $headers,
            query: $query,
            body: $body,
        );
    }

    private function substitutePath(string $url, string $name, mixed $value, bool $encoded): string
    {
        $replacement = $encoded ? (string) $value : rawurlencode((string) $value);
        return str_replace("{{$name}}", $replacement, $url);
    }

    public function getReturnType(): ?ReflectionNamedType
    {
        return $this->returnType;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getResponseType(): ?ResponseType
    {
        return $this->responseType;
    }

    public function isStreaming(): bool
    {
        return $this->isStreaming;
    }

    public function isMultipart(): bool
    {
        return $this->isMultipart;
    }

    public function isFormEncoded(): bool
    {
        return $this->isFormEncoded;
    }
}
