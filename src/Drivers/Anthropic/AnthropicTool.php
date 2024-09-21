<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Drivers\Anthropic;

use EchoLabs\Prism\Drivers\DriverTool;
use EchoLabs\Prism\Tool;

class AnthropicTool extends DriverTool
{
    #[\Override]
    public static function toArray(Tool $tool): array
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
