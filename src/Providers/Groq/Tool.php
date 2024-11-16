<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Groq;

use EchoLabs\Prism\Providers\ProviderTool;
use EchoLabs\Prism\Tool as PrismTool;

class Tool extends ProviderTool
{
    #[\Override]
    public static function toArray(PrismTool $tool): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => collect($tool->parameters())
                        ->keyBy(fn (array $field, string $key): string => $key)
                        ->map(fn (array $field): array => [
                            'description' => $field['description'],
                            'type' => $field['type'],
                        ])
                        ->toArray(),
                    'required' => $tool->requiredParameters(),
                ],
            ],
        ];
    }
}
