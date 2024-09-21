<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use Closure;

class Tool
{
    protected string $name;

    protected string $description;

    /** @var array<int, array<string, string|bool>> */
    protected array $parameters;

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

    public function withParameter(string $name, string $description, string $type = 'string', bool $required = true): self
    {
        $this->parameters[] = [
            'name' => $name,
            'description' => $description,
            'type' => $type,
            'required' => $required,
        ];

        return $this;
    }

    /** @return array<int, string> */
    public function requiredParameters(): array
    {
        return collect($this->parameters)
            ->filter(fn (array $params): bool => (bool) $params['required'])
            ->keyBy('name')
            ->keys()
            ->all();
    }

    /** @return array<int, array<string, string|bool>> */
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
