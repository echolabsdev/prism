<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI;

use EchoLabs\Prism\Providers\ProviderTool;
use EchoLabs\Prism\Tool as PrismTool;

class Tool extends ProviderTool
{
    #[\Override]
    public static function toArray(PrismTool $tool): array
    {
        return [
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
        ];
    }
}
