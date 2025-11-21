<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

use Phpmystic\RetrofitPhp\Http\Request;
use Phpmystic\RetrofitPhp\Http\Response;

interface HttpClient
{
    public function execute(Request $request): Response;
}
