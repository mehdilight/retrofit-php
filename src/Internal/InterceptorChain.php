<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Internal;

use Closure;
use Phpmystic\RetrofitPhp\Contracts\Chain;
use Phpmystic\RetrofitPhp\Contracts\Interceptor;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

final class InterceptorChain implements Chain
{
    private int $index = 0;

    /**
     * @param Interceptor[] $interceptors
     * @param Closure(Request): Response $executor
     */
    public function __construct(
        private Request $request,
        private readonly array $interceptors,
        private readonly Closure $executor,
    ) {}

    public function request(): Request
    {
        return $this->request;
    }

    public function proceed(Request $request): Response
    {
        // Update current request
        $this->request = $request;

        // If there are more interceptors, call the next one
        if ($this->index < count($this->interceptors)) {
            $interceptor = $this->interceptors[$this->index];
            $this->index++;

            return $interceptor->intercept($this);
        }

        // No more interceptors, execute the actual request
        return ($this->executor)($request);
    }
}
