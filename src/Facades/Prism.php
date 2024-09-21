<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Facades;

use BadMethodCallException;
use EchoLabs\Prism\Prism as PrismConcrete;

/**
 * @method static PrismConcrete using(string $driver)
 */
class Prism
{
    /** @param array<int, mixed> $arguments */
    public static function __callStatic(string $method, array $arguments): PrismConcrete
    {
        $instance = new Prism;

        if (method_exists($instance, $method)) {
            return $instance->$method(...$arguments);
        }

        throw new BadMethodCallException("Method {$method} does not exist.");
    }
}
