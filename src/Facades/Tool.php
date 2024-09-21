<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Facades;

use EchoLabs\Prism\Tool as ToolConcrete;

/**
 * @method static self as(string $name)
 * @method static self for(string $description)
 * @method static self using(Closure|callable $fn)
 * @method static self withParameter(string $name, string $description, string $type = 'string', bool $required = true)
 * @method static array<int, string> requiredParameters()
 * @method static array<int, array<string, string|bool>> parameters()
 * @method static string name()
 * @method static string description()
 * @method static string __invoke(...$args)
 */
class Tool
{
    /** @param array<int, mixed> $arguments */
    public static function __callStatic(string $method, array $arguments): ToolConcrete
    {
        $instance = new ToolConcrete;

        if (method_exists($instance, $method)) {
            return $instance->$method(...$arguments);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
