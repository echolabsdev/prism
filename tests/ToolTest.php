<?php

declare(strict_types=1);

namespace Tests;

use EchoLabs\Prism\Facades\Tool as ToolFacade;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Parameters\BooleanParameter;
use EchoLabs\Prism\ValueObjects\Parameters\StringParameter;

it('can return tool details', function (): void {
    $searchTool = (new Tool)
        ->as('search')
        ->for('useful for searching current data')
        ->withParameter(new StringParameter('query', 'the search query'))
        ->using(function (string $query): string {
            expect($query)->toBe('What time is the event?');

            return 'The event is at 3pm eastern';
        });

    expect($searchTool->name())->toBe('search');
    expect($searchTool->description())->toBe('useful for searching current data');
    expect($searchTool->parameters())->toBe([
        'query' => [
            'description' => 'the search query',
            'type' => 'string',
        ],
    ]);

    expect($searchTool->requiredParameters())->toBe(['query']);
});

it('can use a closure', function (): void {
    $searchTool = (new Tool)
        ->as('search')
        ->for('useful for searching current data')
        ->withParameter(new StringParameter('query', 'the search query'))
        ->using(function (string $query): string {
            expect($query)->toBe('What time is the event?');

            return 'The event is at 3pm eastern';

        });

    expect($searchTool->handle('What time is the event?'))
        ->toBe('The event is at 3pm eastern');
});

it('can be used via facade', function (): void {
    $searchTool = ToolFacade::as('search')
        ->for('useful for searching current data')
        ->withParameter(new StringParameter('query', 'the search query'))
        ->using(function (string $query): string {
            expect($query)->toBe('What time is the event?');

            return 'The event is at 3pm eastern';

        });

    expect($searchTool->handle('What time is the event?'))
        ->toBe('The event is at 3pm eastern');
});

it('can use an invokeable', function (): void {
    $fn = new class
    {
        public function __invoke(string $query): string
        {
            expect($query)->toBe('What time is the event?');

            return 'The event is at 3pm eastern';
        }
    };

    $searchTool = (new Tool)
        ->as('search')
        ->for('useful for searching current data')
        ->withParameter(new StringParameter('query', 'the search query'))
        ->using($fn);

    expect($searchTool->handle('What time is the event?'))
        ->toBe('The event is at 3pm eastern');
});

it('can have fluent parameters', function (): void {
    $tool = (new Tool)
        ->as('test tool')
        ->for('not really useful for anything')
        ->withString(name: 'query', description: 'the search query', required: false)
        ->withNumber('age', 'the users age')
        ->withBoolean('active', 'active status')
        ->withArray(
            name: 'items',
            description: 'user requested items',
            items: new StringParameter('itemm', 'an item that the user requested'),
        )
        ->withObject(
            name: 'user',
            description: 'the user object',
            properties: [
                new StringParameter('name', 'the users name'),
                new BooleanParameter('active_status', 'user active status'),
            ],
            requiredFields: [
                'name',
            ]
        );

    expect($tool->parameters()['query'])->toBe([
        'description' => 'the search query',
        'type' => 'string',
    ]);

    expect($tool->parameters()['age'])->toBe([
        'description' => 'the users age',
        'type' => 'number',
    ]);

    expect($tool->parameters()['active'])->toBe([
        'description' => 'active status',
        'type' => 'boolean',
    ]);

    expect($tool->parameters()['items'])->toBe([
        'description' => 'user requested items',
        'type' => 'array',
        'items' => [
            'description' => 'an item that the user requested',
            'type' => 'string',
        ],
    ]);

    expect($tool->parameters()['user'])->toBe([
        'description' => 'the user object',
        'type' => 'object',
        'properties' => [
            'name' => [
                'description' => 'the users name',
                'type' => 'string',
            ],
            'active_status' => [
                'description' => 'user active status',
                'type' => 'boolean',
            ],
        ],
        'required' => ['name'],
        'additionalProperties' => false,
    ]);

    expect($tool->requiredParameters())->toBe([
        'age',
        'active',
        'items',
        'user',
    ]);
});
