<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\OpenAI\Maps;

use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Tool;

class ToolMap
{
    /**
     * @param  Tool[]  $tools
     * @return array<string, mixed>
     */
    public static function Map(array $tools): array
    {
        return array_map(fn (Tool $tool): array => array_filter([
            'type' => 'function',
            'function' => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                ...count($tool->parameters()) ? [
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $tool->parameters(),
                        'required' => $tool->requiredParameters(),
                    ],
                ] : [],
            ],
            'strict' => data_get($tool->providerMeta(Provider::OpenAI), 'strict', null),
        ]), $tools);
    }
}
