<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Parameters;

use EchoLabs\Prism\Contracts\Parameter;

class ObjectParameter implements Parameter
{
    /**
     * @param  array<int, Parameter>  $properties
     * @param  array<int, string>  $requiredFields
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $properties,
        public readonly array $requiredFields = [],
        public readonly bool $allowAdditionalProperties = false,
    ) {}

    #[\Override]
    public function name(): string
    {
        return $this->name;
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'type' => 'object',
            'properties' => $this->propertiesArray(),
            'required' => $this->requiredFields,
            'additionalProperties' => $this->allowAdditionalProperties,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function propertiesArray(): array
    {
        return collect($this->properties)
            ->keyBy(fn (Parameter $parameter): string => $parameter->name())
            ->map(fn (Parameter $parameter): array => $parameter->toArray())
            ->toArray();
    }
}
