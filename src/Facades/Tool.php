<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Facades;

use Closure;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Tool as ToolConcrete;

/**
 * @method static self as(string $name)
 * @method static self for(string $description)
 * @method static self using(Closure|callable $fn)
 * @method static self withParameter(Schema $parameter, bool $required = true)
 * @method static self withStringParameter(string $name, string $description, bool $required = true)
 * @method static self withNumberParameter(string $name, string $description, bool $required = true)
 * @method static self withBooleanParameter(string $name, string $description, bool $required = true)
 * @method static self withEnumParemeter(string $name, string $description, array $options, bool $required = true)
 * @method static self withArrayParameter(string $name, string $description, Schema $items, bool $required = true)
 * @method static self withObjectParameter(string $name, string $description, array $properties, array $requiredFields = [], bool $allowAdditionalProperties = false, bool $required = true)
 * @method static array<int, string> requiredParameters()
 * @method static array<string, array<string, mixed>> parameters()
 * @method static string name()
 * @method static string description()
 * @method static string handle(string|int|float ...$args)
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
