<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Providers\Anthropic\Tool as AnthropicTool;
use EchoLabs\Prism\Tool;

it('maps tools', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(AnthropicTool::toArray($tool))->toBe([
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
