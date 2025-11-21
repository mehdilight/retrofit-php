<?php

declare(strict_types=1);

namespace Phpmystic\RetrofitPhp\Contracts;

interface Converter
{
    public function convert(mixed $value): mixed;
}
