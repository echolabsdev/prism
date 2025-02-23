<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral\Maps;

use EchoLabs\Prism\Tool;

class ToolMap
{
    /**
     * @param  Tool[]  $tools
     * @return array<mixed>
     */
    public static function map(array $tools): array
    {
        return array_map(fn (Tool $tool): array => [
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $tool->parameters(),
                    'required' => $tool->requiredParameters(),
                ],
            ],
        ], $tools);
    }
}
