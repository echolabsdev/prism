<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use EchoLabs\Prism\Providers\Gemini\Maps\ToolMap;
use EchoLabs\Prism\Tool;

it('maps tools to gemini format', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(ToolMap::map([$tool]))->toBe([[
        'functionDeclarations' => [[
            'name' => $tool->name(),
            'description' => $tool->description(),
            'parameters' => [
                'type' => 'object',
                'properties' => $tool->parameters(),
                'required' => $tool->requiredParameters(),
            ],
        ]],
    ]]);
});

it('maps multiple tools', function (): void {
    $tools = [
        (new Tool)
            ->as('search')
            ->for('Searching the web')
            ->withStringParameter('query', 'the detailed search query')
            ->using(fn (): string => '[Search results]'),
        (new Tool)
            ->as('weather')
            ->for('Get weather info')
            ->withStringParameter('location', 'the location')
            ->using(fn (): string => '[Weather info]'),
    ];

    $mapped = ToolMap::map($tools);
    expect($mapped)->toHaveCount(2);
    expect($mapped[0]['functionDeclarations'][0]['name'])->toBe('search');
    expect($mapped[1]['functionDeclarations'][0]['name'])->toBe('weather');
});

it('returns empty array for no tools', function (): void {
    expect(ToolMap::map([]))->toBe([]);
});
