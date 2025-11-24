<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp;

use ReflectionClass;
use ReflectionMethod;
use Phpmystic\RetrofitPhp\Cache\CacheInterface;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Contracts\CallAdapterFactory;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Internal\ServiceProxy;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;

final class Retrofit
{
    /** @var array<class-string, ServiceProxy> */
    private array $serviceProxies = [];

    /**
     * @param ConverterFactory[] $converterFactories
     * @param CallAdapterFactory[] $callAdapterFactories
     * @param Interceptor[] $interceptors
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly HttpClient $httpClient,
        private readonly array $converterFactories,
        private readonly array $callAdapterFactories,
        private readonly array $interceptors = [],
        private readonly ?RetryPolicy $retryPolicy = null,
        private readonly ?CacheInterface $cache = null,
        private readonly ?CachePolicy $cachePolicy = null,
    ) {}

    public static function builder(): RetrofitBuilder
    {
        return new RetrofitBuilder();
    }

    /**
     * @template TClass
     *
     * @param class-string<TClass> $interface
     *
     * @return TClass
     * @throws \ReflectionException
     */
    public function create(string $interface)
    {
        $reflection = new ReflectionClass($interface);

        if (!$reflection->isInterface()) {
            throw new \InvalidArgumentException("'{$interface}' must be an interface.");
        }

        $proxy = $this->getServiceProxy($interface);

        return $this->createProxyInstance($reflection, $proxy);
    }

    /**
     * @param class-string $interface
     */
    private function getServiceProxy(string $interface): ServiceProxy
    {
        if (!isset($this->serviceProxies[$interface])) {
            $this->serviceProxies[$interface] = new ServiceProxy(
                $interface,
                $this->baseUrl,
                $this->httpClient,
                $this->converterFactories,
                $this->interceptors,
                $this->retryPolicy,
                $this->cache,
                $this->cachePolicy,
            );
        }

        return $this->serviceProxies[$interface];
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return T
     */
    private function createProxyInstance(ReflectionClass $reflection, ServiceProxy $proxy): object
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $className = $this->generateProxyClassName($reflection->getName());

        if (!class_exists($className)) {
            $code = $this->generateProxyClass($reflection, $className);
            eval($code);
        }

        return new $className($proxy);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function generateProxyClass(ReflectionClass $reflection, string $className): string
    {
        $interfaceName = $reflection->getName();
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $methodsCode = '';
        foreach ($methods as $method) {
            $methodsCode .= $this->generateProxyMethod($method);
        }

        // Extract just the class name without namespace for the class declaration
        $shortClassName = substr($className, strrpos($className, '\\') + 1);
        $namespace = substr($className, 0, strrpos($className, '\\'));

        return <<<PHP
namespace {$namespace};

class {$shortClassName} implements \\{$interfaceName} {
    public function __construct(
        private readonly \Phpmystic\RetrofitPhp\Internal\ServiceProxy \$proxy,
    ) {}

    {$methodsCode}
}
PHP;
    }

    private function generateProxyMethod(ReflectionMethod $method): string
    {
        $methodName = $method->getName();
        $params = [];
        $args = [];

        foreach ($method->getParameters() as $param) {
            $paramStr = '';

            // Type
            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $paramStr .= ($type->allowsNull() ? '?' : '') . $this->getTypeName($type) . ' ';
                }
            }

            // Name
            $paramStr .= '$' . $param->getName();

            // Default value
            if ($param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
                $paramStr .= ' = ' . var_export($default, true);
            }

            $params[] = $paramStr;
            $args[] = '$' . $param->getName();
        }

        $paramsStr = implode(', ', $params);
        $argsStr = implode(', ', $args);

        // Return type
        $returnType = '';
        if ($method->hasReturnType()) {
            $type = $method->getReturnType();
            if ($type instanceof \ReflectionNamedType) {
                $returnType = ': ' . ($type->allowsNull() ? '?' : '') . $this->getTypeName($type);
            }
        }

        return <<<PHP

    public function {$methodName}({$paramsStr}){$returnType}
    {
        \$call = \$this->proxy->invoke('{$methodName}', [{$argsStr}]);
        \$response = \$call->execute();
        return \$response->body;
    }

PHP;
    }

    private function getTypeName(\ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if ($type->isBuiltin()) {
            return $name;
        }

        return '\\' . $name;
    }

    private function generateProxyClassName(string $interfaceName): string
    {
        return 'Phpmystic\\RetrofitPhp\\Generated\\' . str_replace('\\', '_', $interfaceName) . '_Proxy';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function httpClient(): HttpClient
    {
        return $this->httpClient;
    }

    /**
     * @return ConverterFactory[]
     */
    public function converterFactories(): array
    {
        return $this->converterFactories;
    }

    /**
     * @return CallAdapterFactory[]
     */
    public function callAdapterFactories(): array
    {
        return $this->callAdapterFactories;
    }
}
