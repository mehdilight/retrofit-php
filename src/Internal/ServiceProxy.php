<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

use ReflectionClass;
use ReflectionMethod;
use Phpmystic\RetrofitPhp\Contracts\Call;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use RuntimeException;

final class ServiceProxy
{
    /** @var array<string, ServiceMethod> */
    private array $serviceMethods = [];

    /**
     * @param class-string $interface
     * @param list<ConverterFactory> $converterFactories
     */
    public function __construct(
        private readonly string $interface,
        private readonly string $baseUrl,
        private readonly HttpClient $httpClient,
        private readonly array $converterFactories,
    ) {
        $this->validateInterface();
        $this->loadServiceMethods();
    }

    private function validateInterface(): void
    {
        $reflection = new ReflectionClass($this->interface);
        if (!$reflection->isInterface()) {
            throw new RuntimeException("'{$this->interface}' must be an interface.");
        }
    }

    private function loadServiceMethods(): void
    {
        $reflection = new ReflectionClass($this->interface);

        foreach ($reflection->getMethods() as $method) {
            $this->serviceMethods[$method->getName()] = new ServiceMethod(
                $method,
                $this->baseUrl,
                $this->converterFactories,
            );
        }
    }

    /**
     * @param array<int, mixed> $args
     */
    public function invoke(string $methodName, array $args): Call
    {
        if (!isset($this->serviceMethods[$methodName])) {
            throw new RuntimeException("Method '{$methodName}' not found on interface '{$this->interface}'.");
        }

        $serviceMethod = $this->serviceMethods[$methodName];
        $request = $serviceMethod->buildRequest($args);

        // Find converters
        $requestConverter = $this->findRequestConverter($serviceMethod);
        $responseConverter = $this->findResponseConverter($serviceMethod);

        return new DefaultCall(
            $this->httpClient,
            $request,
            $requestConverter,
            $responseConverter,
        );
    }

    private function findRequestConverter(ServiceMethod $serviceMethod): ?\Phpmystic\RetrofitPhp\Contracts\Converter
    {
        foreach ($this->converterFactories as $factory) {
            $converter = $factory->requestBodyConverter(null);
            if ($converter !== null) {
                return $converter;
            }
        }
        return null;
    }

    private function findResponseConverter(ServiceMethod $serviceMethod): ?\Phpmystic\RetrofitPhp\Contracts\Converter
    {
        $returnType = $serviceMethod->getReturnType();
        foreach ($this->converterFactories as $factory) {
            $converter = $factory->responseBodyConverter($returnType);
            if ($converter !== null) {
                return $converter;
            }
        }
        return null;
    }
}
