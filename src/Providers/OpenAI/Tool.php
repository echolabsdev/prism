<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Providers\ProviderTool;
use EchoLabs\Prism\Tool as PrismTool;

class Tool extends ProviderTool
{
    #[\Override]
    public static function toArray(PrismTool $tool): array
    {
        return array_filter([
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
            'strict' => data_get($tool->providerMeta(Provider::OpenAI), 'strict', null),
        ]);
    }
}
