<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use ArgumentCountError;
use Closure;
use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\EnumSchema;
use EchoLabs\Prism\Schema\NumberSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use InvalidArgumentException;
use Throwable;
use TypeError;

class Tool
{
    use HasProviderMeta;

    protected string $name = '';

    protected string $description;

    /** @var array<string, array<string, mixed>> */
    protected array $parameters = [];

    /** @var array <int, string> */
    protected array $requiredParameters = [];

    /** @var Closure():string|callable():string */
    protected $fn;

    public function as(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function for(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function using(Closure|callable $fn): self
    {
        $this->fn = $fn;

        return $this;
    }

    public function withParameter(Schema $parameter, bool $required = true): self
    {
        $this->parameters[$parameter->name()] = $parameter->toArray();

        if ($required) {
            $this->requiredParameters[] = $parameter->name();
        }

        return $this;
    }

    public function withStringParameter(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new StringSchema($name, $description), $required);

        return $this;
    }

    public function withNumberParameter(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new NumberSchema($name, $description), $required);

        return $this;
    }

    public function withBooleanParameter(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new BooleanSchema($name, $description), $required);

        return $this;
    }

    public function withArrayParameter(
        string $name,
        string $description,
        Schema $items,
        bool $required = true,
    ): self {
        $this->withParameter(new ArraySchema($name, $description, $items), $required);

        return $this;
    }

    /**
     * @param  array<int, Schema>  $properties
     * @param  array<int, string>  $requiredFields
     */
    public function withObjectParameter(
        string $name,
        string $description,
        array $properties,
        array $requiredFields = [],
        bool $allowAdditionalProperties = false,
        bool $required = true,
    ): self {

        $this->withParameter(new ObjectSchema(
            $name,
            $description,
            $properties,
            $requiredFields,
            $allowAdditionalProperties,
        ), $required);

        return $this;
    }

    /**
     * @param  array<int, string|int|float>  $options
     */
    public function withEnumParameter(
        string $name,
        string $description,
        array $options,
        bool $required = true,
    ): self {
        $this->withParameter(new EnumSchema($name, $description, $options), $required);

        return $this;
    }

    /** @return array<int, string> */
    public function requiredParameters(): array
    {
        return $this->requiredParameters;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @param  string|int|float  $args
     *
     * @throws PrismException|Throwable
     */
    public function handle(...$args): string
    {
        try {
            return call_user_func($this->fn, ...$args);
        } catch (ArgumentCountError|InvalidArgumentException|TypeError $e) {
            throw PrismException::invalidParameterInTool($this->name, $e);
        }
    }
}
