<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Tool;

it('maps tools', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(ToolMap::map($tool))->toBe([
        'name' => 'search',
        'description' => 'Searching the web',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'query' => [
                    'description' => 'the detailed search query',
                    'type' => 'string',
                ],
            ],
            'required' => ['query'],
        ],
    ]);
});
