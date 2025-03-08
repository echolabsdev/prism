<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Providers\Anthropic\Enums\AnthropicCacheType;
use PrismPHP\Prism\Providers\Anthropic\Maps\ToolMap;
use PrismPHP\Prism\Tool;

it('maps tools', function (): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]');

    expect(ToolMap::map([$tool]))->toBe([[
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
    ]]);
});

it('sets the cache typeif cacheType providerMeta is set on tool', function (mixed $cacheType): void {
    $tool = (new Tool)
        ->as('search')
        ->for('Searching the web')
        ->withStringParameter('query', 'the detailed search query')
        ->using(fn (): string => '[Search results]')
        ->withProviderMeta(Provider::Anthropic, ['cacheType' => $cacheType]);

    expect(ToolMap::map([$tool]))->toBe([[
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
        'cache_control' => ['type' => 'ephemeral'],
    ]]);
})->with([
    'ephemeral',
    AnthropicCacheType::Ephemeral->value,
]);
