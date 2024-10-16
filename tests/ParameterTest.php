<?php

declare(strict_types=1);

namespace Tests;

use EchoLabs\Prism\ValueObjects\Parameters\ArrayParameter;
use EchoLabs\Prism\ValueObjects\Parameters\EnumParameter;
use EchoLabs\Prism\ValueObjects\Parameters\NumberParameter;
use EchoLabs\Prism\ValueObjects\Parameters\ObjectParameter;
use EchoLabs\Prism\ValueObjects\Parameters\StringParameter;

it('they can have nested properties', function (): void {
    $schema = new ObjectParameter(
        name: 'user',
        description: 'a user object',
        properties: [
            new StringParameter('name', 'the users name'),
            new NumberParameter('age', 'the users age', false),
            new EnumParameter(
                name: 'status',
                description: 'the users status',
                options: [
                    'active',
                    'inactive',
                    'suspended',
                ]
            ),
            new ArrayParameter(
                name: 'hobbies',
                description: 'the users hobbies',
                item: new StringParameter('hobby', 'the users hobby')
            ),
            new ObjectParameter(
                name: 'address',
                description: 'the users address',
                properties: [
                    new StringParameter('street', 'the street part of the address'),
                    new StringParameter('city', 'the city part of the address'),
                    new StringParameter('country', 'the country part of the address'),
                    new NumberParameter('zip', 'the zip code part of the address'),
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
