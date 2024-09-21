<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Drivers\OpenAI;

use EchoLabs\Prism\Drivers\DriverTool;
use EchoLabs\Prism\Tool;

class OpenAITool extends DriverTool
{
    #[\Override]
    public static function toArray(Tool $tool): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => [
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
            ],
        ];
    }
}
