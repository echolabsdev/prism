<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Maps;

use EchoLabs\Prism\Tool as PrismTool;

class ToolMap
{
    /**
     * @return array{name: string, description: string, input_schema: array{type: string, properties: array<string, mixed>, required: string[]}}
     */
    public static function map(PrismTool $tool): array
    {
        return [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'input_schema' => [
                'type' => 'object',
                'properties' => $tool->parameters(),
                'required' => $tool->requiredParameters(),
            ],
        ];
    }
}
