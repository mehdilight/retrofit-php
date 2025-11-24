<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp;

use InvalidArgumentException;
use Phpmystic\RetrofitPhp\Cache\CacheInterface;
use Phpmystic\RetrofitPhp\Cache\CachePolicy;
use Phpmystic\RetrofitPhp\Contracts\CallAdapterFactory;
use Phpmystic\RetrofitPhp\Contracts\ConverterFactory;
use Phpmystic\RetrofitPhp\Contracts\HttpClient;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Retry\RetryPolicy;

final class RetrofitBuilder
{
    private ?string $baseUrl = null;
    private ?HttpClient $httpClient = null;

    /** @var ConverterFactory[] */
    private array $converterFactories = [];

    /** @var CallAdapterFactory[] */
    private array $callAdapterFactories = [];

    /** @var Interceptor[] */
    private array $interceptors = [];

    private ?RetryPolicy $retryPolicy = null;
    private ?CacheInterface $cache = null;
    private ?CachePolicy $cachePolicy = null;

    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    public function client(HttpClient $client): self
    {
        $this->httpClient = $client;
        return $this;
    }

    public function addConverterFactory(ConverterFactory $factory): self
    {
        $this->converterFactories[] = $factory;
        return $this;
    }

    public function addCallAdapterFactory(CallAdapterFactory $factory): self
    {
        $this->callAdapterFactories[] = $factory;
        return $this;
    }

    public function addInterceptor(Interceptor $interceptor): self
    {
        $this->interceptors[] = $interceptor;
        return $this;
    }

    public function retryPolicy(RetryPolicy $retryPolicy): self
    {
        $this->retryPolicy = $retryPolicy;
        return $this;
    }

    public function cache(CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    public function cachePolicy(CachePolicy $cachePolicy): self
    {
        $this->cachePolicy = $cachePolicy;
        return $this;
    }

    public function build(): Retrofit
    {
        if ($this->baseUrl === null) {
            throw new InvalidArgumentException('Base URL required. Call baseUrl() before build().');
        }

        if ($this->httpClient === null) {
            throw new InvalidArgumentException('HTTP client required. Call client() before build().');
        }

        return new Retrofit(
            $this->baseUrl,
            $this->httpClient,
            $this->converterFactories,
            $this->callAdapterFactories,
            $this->interceptors,
            $this->retryPolicy,
            $this->cache,
            $this->cachePolicy,
        );
    }
}
