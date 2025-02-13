<?php

namespace EchoLabs\Prism\Providers\Gemini\Maps;

use EchoLabs\Prism\Contracts\Schema;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\NumberSchema;
use EchoLabs\Prism\Schema\ObjectSchema;

class SchemaMap
{
    public function __construct(
        private readonly Schema $schema,
    ) {}

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return array_merge([
            'type' => $this->mapType(),
            ...array_filter([
                ...$this->schema->toArray(),
                'additionalProperties' => null,
            ]),
        ], array_filter([
            'items' => property_exists($this->schema, 'items') ?
                (new self($this->schema->items))->toArray() :
                null,
            'properties' => property_exists($this->schema, 'properties') ?
                array_reduce($this->schema->properties, fn (array $carry, Schema $property) => [
                    ...$carry,
                    $property->name() => (new self($property))->toArray(),
                ], []) :
                null,
        ]));
    }

    protected function mapType(): string
    {
        switch ($this->schema::class) {
            case ArraySchema::class:
                return 'array';
            case BooleanSchema::class:
                return 'boolean';
            case NumberSchema::class:
                return 'number';
            case ObjectSchema::class:
                return 'object';
            default:
                return 'string';
        }
    }
}
