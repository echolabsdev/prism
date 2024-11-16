<?php

declare(strict_types=1);

namespace Tests\Providers\Mistral;

use EchoLabs\Prism\Providers\Mistral\Maps\ToolMap;
use EchoLabs\Prism\Tool;

it('maps tools', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(ToolMap::map([$tool]))->toBe([[
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
    ]]);
});
