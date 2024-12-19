<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Maps;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Tool as PrismTool;
use UnitEnum;

class ToolMap
{
    /**
     * @param  PrismTool[]  $tools
     * @return array<string, mixed>
     */
    public static function map(array $tools): array
    {
        return array_map(function (PrismTool $tool): array {
            $cacheType = data_get($tool->providerMeta(Provider::Anthropic), 'cacheType', null);

            return array_filter([
                'name' => $tool->name(),
                'description' => $tool->description(),
                'input_schema' => [
                    'type' => 'object',
                    'properties' => $tool->parameters(),
                    'required' => $tool->requiredParameters(),
                ],
                'cache_control' => $cacheType ? ['type' => $cacheType instanceof UnitEnum ? $cacheType->name : $cacheType] : null,
            ]);
        }, $tools);
    }
}
