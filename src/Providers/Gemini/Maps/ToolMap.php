<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Maps;

use EchoLabs\Prism\Tool;

class ToolMap
{
    /**
     * @param  array<Tool>  $tools
     * @return array<array<string, mixed>>
     */
    public static function map(array $tools): array
    {
        if ($tools === []) {
            return [];
        }

        return array_map(fn (Tool $tool): array => [
            'functionDeclarations' => [
                [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $tool->parameters(),
                        'required' => $tool->requiredParameters(),
                    ],
                ],
            ],
        ], $tools);
    }
}
