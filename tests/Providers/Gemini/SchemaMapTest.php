<?php

declare(strict_types=1);

use PrismPHP\Prism\Providers\Gemini\Maps\SchemaMap;
use PrismPHP\Prism\Schema\ArraySchema;
use PrismPHP\Prism\Schema\BooleanSchema;
use PrismPHP\Prism\Schema\EnumSchema;
use PrismPHP\Prism\Schema\NumberSchema;
use PrismPHP\Prism\Schema\ObjectSchema;
use PrismPHP\Prism\Schema\StringSchema;

it('maps array schema correctly', function (): void {
    $map = (new SchemaMap(new ArraySchema(
        name: 'testArray',
        description: 'test array description',
        items: new StringSchema(
            name: 'testName',
            description: 'test string description',
            nullable: true,
        ),
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test array description',
        'type' => 'array',
        'items' => [
            'description' => 'test string description',
            'type' => 'string',
            'nullable' => true,
        ],
        'nullable' => true,
    ]);
});

it('maps boolean schema correctly', function (): void {
    $map = (new SchemaMap(new BooleanSchema(
        name: 'testBoolean',
        description: 'test description',
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test description',
        'type' => 'boolean',
        'nullable' => true,
    ]);
});

it('maps enum schema correctly', function (): void {
    $map = (new SchemaMap(new EnumSchema(
        name: 'testEnum',
        description: 'test description',
        options: ['option1', 'option2'],
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test description',
        'enum' => ['option1', 'option2'],
        'type' => 'string',
        'nullable' => true,
    ]);
});

it('maps number schema correctly', function (): void {
    $map = (new SchemaMap(new NumberSchema(
        name: 'testNumber',
        description: 'test description',
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test description',
        'type' => 'number',
        'nullable' => true,
    ]);
});

it('maps string schema correctly', function (): void {
    $map = (new SchemaMap(new StringSchema(
        name: 'testName',
        description: 'test description',
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test description',
        'type' => 'string',
        'nullable' => true,
    ]);
});

it('maps object schema correctly', function (): void {
    $map = (new SchemaMap(new ObjectSchema(
        name: 'testObject',
        description: 'test object description',
        properties: [
            new StringSchema(
                name: 'testName',
                description: 'test string description',
            ),
        ],
        requiredFields: ['testName'],
        allowAdditionalProperties: true,
        nullable: true,
    )))->toArray();

    expect($map)->toBe([
        'description' => 'test object description',
        'type' => 'object',
        'properties' => [
            'testName' => [
                'description' => 'test string description',
                'type' => 'string',
            ],
        ],
        'required' => ['testName'],
        'nullable' => true,
    ]);
});
