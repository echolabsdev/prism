<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Schema;

use EchoLabs\Prism\Contracts\Schema;

class EnumSchema implements Schema
{
    /**
     * @param  array<int, string|int|float>  $options
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $options,
        public readonly bool $nullable = false,
    ) {
    }

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
            'enum' => $this->options,
            'type' => $this->getOptionTypes(),
        ];
    }

    /**
     * @return string|array<int, string>
     */
    protected function getOptionTypes(): array|string
    {
        $types = [];
        foreach ($this->options as $option) {
            $type = gettype($option);
            $types[] = match ($type) {
                'integer', 'double' => 'number',
                'string' => 'string'
            };
        }

        if ($this->nullable) {
            $types[] = 'null';
        }

        $types = collect($types)
            ->unique()
            ->values()
            ->all();

        if (count($types) === 1) {
            return $types[0];
        }

        return $types;
    }
}
