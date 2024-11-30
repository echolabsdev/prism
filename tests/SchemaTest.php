<?php

declare(strict_types=1);

namespace Tests;

use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\EnumSchema;
use EchoLabs\Prism\Schema\NumberSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;

it('they can have nested properties', function (): void {
    $schema = new ObjectSchema(
        name: 'user',
        description: 'a user object',
        properties: [
            new StringSchema('name', 'the users name'),
            new NumberSchema('age', 'the users age'),
            new EnumSchema(
                name: 'status',
                description: 'the users status',
                options: [
                    'active',
                    'inactive',
                    'suspended',
                ]
            ),
            new ArraySchema(
                name: 'hobbies',
                description: 'the users hobbies',
                items: new StringSchema('hobby', 'the users hobby')
            ),
            new ObjectSchema(
                name: 'address',
                description: 'the users address',
                properties: [
                    new StringSchema('street', 'the street part of the address'),
                    new StringSchema('city', 'the city part of the address'),
                    new StringSchema('country', 'the country part of the address'),
                    new NumberSchema('zip', 'the zip code part of the address'),
                ],
                requiredFields: ['street', 'city', 'country', 'zip']
            ),
        ]
    );

    expect($schema->toArray())->toBe([
        'description' => 'a user object',
        'type' => 'object',
        'properties' => [
            'name' => [
                'description' => 'the users name',
                'type' => 'string',
            ],
            'age' => [
                'description' => 'the users age',
                'type' => 'number',
            ],
            'status' => [
                'description' => 'the users status',
                'enum' => [
                    'active',
                    'inactive',
                    'suspended',
                ],
            ],
            'hobbies' => [
                'description' => 'the users hobbies',
                'type' => 'array',
                'items' => [
                    'description' => 'the users hobby',
                    'type' => 'string',
                ],
            ],
            'address' => [
                'description' => 'the users address',
                'type' => 'object',
                'properties' => [
                    'street' => [
                        'description' => 'the street part of the address',
                        'type' => 'string',
                    ],
                    'city' => [
                        'description' => 'the city part of the address',
                        'type' => 'string',
                    ],
                    'country' => [
                        'description' => 'the country part of the address',
                        'type' => 'string',
                    ],
                    'zip' => [
                        'description' => 'the zip code part of the address',
                        'type' => 'number',
                    ],
                ],
                'required' => ['street', 'city', 'country', 'zip'],
                'additionalProperties' => false,
            ],
        ],
        'required' => [],
        'additionalProperties' => false,
    ]);
});

it('they can be nullable', function(): void {
    $schema = new ObjectSchema(
        name: 'user',
        description: 'a user object',
        properties: [
            new StringSchema('name', 'the users name', nullable: true),
            new NumberSchema('age', 'the users age', nullable: true),
            new EnumSchema(
                name: 'status',
                description: 'the users status',
                options: [
                    'active',
                    'inactive',
                    'suspended',
                ],
                nullable: true
            ),
            new ArraySchema(
                name: 'hobbies',
                description: 'the users hobbies',
                items: new StringSchema('hobby', 'the users hobby'),
                nullable: true
            ),
            new BooleanSchema(name: 'is_admin', description: 'is an administrative user', nullable: true),
            new ObjectSchema(
                name: 'address',
                description: 'the users address',
                properties: [
                    new StringSchema('street', 'the street part of the address'),
                    new StringSchema('city', 'the city part of the address'),
                    new StringSchema('country', 'the country part of the address'),
                    new NumberSchema('zip', 'the zip code part of the address'),
                ],
                requiredFields: ['street', 'city', 'country', 'zip']
            ),
        ],
        nullable: true
    );

    expect($schema->toArray())->toBe([
        'description' => 'a user object',
        'type' => ['object', 'null'],
        'properties' => [
            'name' => [
                'description' => 'the users name',
                'type' => ['string', 'null'],
            ],
            'age' => [
                'description' => 'the users age',
                'type' => ['number', 'null'],
            ],
            'status' => [
                'description' => 'the users status',
                'enum' => [
                    'active',
                    'inactive',
                    'suspended',
                ],
            ],
            'hobbies' => [
                'description' => 'the users hobbies',
                'type' => ['array', 'null'],
                'items' => [
                    'description' => 'the users hobby',
                    'type' => 'string',
                ],
            ],
            'is_admin' => [
                'description' => 'is an administrative user',
                'type' => ['boolean', 'null'],
            ],
            'address' => [
                'description' => 'the users address',
                'type' => 'object',
                'properties' => [
                    'street' => [
                        'description' => 'the street part of the address',
                        'type' => 'string',
                    ],
                    'city' => [
                        'description' => 'the city part of the address',
                        'type' => 'string',
                    ],
                    'country' => [
                        'description' => 'the country part of the address',
                        'type' => 'string',
                    ],
                    'zip' => [
                        'description' => 'the zip code part of the address',
                        'type' => 'number',
                    ],
                ],
                'required' => ['street', 'city', 'country', 'zip'],
                'additionalProperties' => false,
            ],
        ],
        'required' => [],
        'additionalProperties' => false,
    ]);
});