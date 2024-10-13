<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic;

use EchoLabs\Prism\Providers\ProviderTool;
use EchoLabs\Prism\Tool as PrismTool;

class Tool extends ProviderTool
{
    #[\Override]
    public static function toArray(PrismTool $tool): array
    {
        return [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'input_schema' => [
                'type' => 'object',
                'properties' => collect($tool->parameters())
                    ->keyBy('name')
                    ->map(fn (array $field): array => [
                        'description' => $field['description'],
                        'type' => $field['type'],
                    ])
                    ->toArray(),
                'required' => $tool->requiredParameters(),
            ],
        ];
    }
}
