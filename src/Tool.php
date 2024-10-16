<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use Closure;
use EchoLabs\Prism\Contracts\Parameter;
use EchoLabs\Prism\ValueObjects\Parameters\ArrayParameter;
use EchoLabs\Prism\ValueObjects\Parameters\BooleanParameter;
use EchoLabs\Prism\ValueObjects\Parameters\EnumParameter;
use EchoLabs\Prism\ValueObjects\Parameters\NumberParameter;
use EchoLabs\Prism\ValueObjects\Parameters\ObjectParameter;
use EchoLabs\Prism\ValueObjects\Parameters\StringParameter;

class Tool
{
    protected string $name;

    protected string $description;

    /** @var array<string, array<string, mixed>> */
    protected array $parameters;

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

    /** @param Closure():string|callable():string $fn */
    public function using(Closure|callable $fn): self
    {
        $this->fn = $fn;

        return $this;
    }

    public function withParameter(Parameter $parameter, bool $required = true): self
    {
        $this->parameters[$parameter->name()] = $parameter->toArray();

        if ($required) {
            $this->requiredParameters[] = $parameter->name();
        }

        return $this;
    }

    public function withString(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new StringParameter($name, $description), $required);

        return $this;
    }

    public function withNumber(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new NumberParameter($name, $description), $required);

        return $this;
    }

    public function withBoolean(string $name, string $description, bool $required = true): self
    {
        $this->withParameter(new BooleanParameter($name, $description), $required);

        return $this;
    }

    public function withArray(
        string $name,
        string $description,
        Parameter $items,
        bool $required = true,
    ): self {
        $this->withParameter(new ArrayParameter($name, $description, $items), $required);

        return $this;
    }

    /**
     * @param  array<int, Parameter>  $properties
     * @param  array<int, string>  $requiredFields
     */
    public function withObject(
        string $name,
        string $description,
        array $properties,
        array $requiredFields = [],
        bool $allowAdditionalProperties = false,
        bool $required = true,
    ): self {

        $this->withParameter(new ObjectParameter(
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
    public function withEnum(string $name, string $description, array $options): self
    {
        $this->withParameter(new EnumParameter($name, $description, $options));

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

    /** @param string|int|float $args */
    public function handle(...$args): string
    {
        return call_user_func($this->fn, ...$args);
    }
}
