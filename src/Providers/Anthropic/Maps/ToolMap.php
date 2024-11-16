<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Maps;

use EchoLabs\Prism\Tool as PrismTool;

class ToolMap
{
    /**
     * @param  PrismTool[]  $tools
     * @return array<string, mixed>
     */
    public static function map(array $tools): array
    {
        return array_map(fn (PrismTool $tool): array => [
            'name' => $tool->name(),
            'description' => $tool->description(),
            'input_schema' => [
                'type' => 'object',
                'properties' => $tool->parameters(),
                'required' => $tool->requiredParameters(),
            ],
        ], $tools);
    }
}
