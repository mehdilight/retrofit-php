<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use GuzzleHttp\Promise\PromiseInterface;
use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

interface HttpClient
{
    public function execute(Request $request): Response;

    /**
     * Execute the request asynchronously.
     *
     * @return PromiseInterface<Response>
     */
    public function executeAsync(Request $request): PromiseInterface;
}
