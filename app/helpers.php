<?php

use App\Support\Tr;

if (! function_exists('t')) {
    function t(string $key, mixed ...$args): string
    {
        return Tr::get($key, $args);
    }
}

if (! function_exists('tn')) {
    function tn(string $key, int $n): string
    {
        return Tr::choice($key, $n);
    }
}

if (! function_exists('r1')) {
    function r1(float $n): float
    {
        return floor($n * 10 + 0.5) / 10;
    }
}
