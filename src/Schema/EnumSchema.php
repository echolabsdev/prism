<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Schema;

use EchoLabs\Prism\Contracts\Schema;
use Illuminate\Support\Collection;

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
            'enum' => $this->options,
            'type' => $this->getOptionTypes(),
        ];
    }

    /**
     * @return string|array<int, string>
     */
    protected function getOptionTypes(): array|string
    {
        // @phpstan-ignore return.type
        return collect($this->options)
            ->map(fn (mixed $option): string => match (gettype($option)) {
                'integer', 'double' => 'number',
                'string' => 'string'
            })
            ->when(
                $this->nullable,
                fn (Collection $collection): Collection => $collection->push('null')
            )
            ->unique()
            ->values()
            ->when(
                fn (Collection $collection): bool => $collection->count() === 1,
                fn (Collection $collection): string => $collection[0],
                fn (Collection $collection): array => $collection->all()
            );
    }
}
