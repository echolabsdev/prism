<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\ArraySchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use Tests\Fixtures\FixtureResponse;

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'gemini/structured');

    $schema = new ObjectSchema(
        name: 'recipe',
        description: 'A recipe for the day',
        properties: [
            new StringSchema(
                name: 'recipe_name',
                description: 'The title of the recipe'
            ),
            new ArraySchema(
                name: 'ingredients',
                description: 'The ingredients needed for the recipe',
                items: new ObjectSchema(
                    name: 'ingredient',
                    description: 'An ingredient for the recipe',
                    properties: [
                        new StringSchema(
                            name: 'name', description: 'The name of the ingredient'
                        ),
                        new StringSchema(
                            name: 'quantity', description: 'The quantity of the ingredient'
                        ),
                        new StringSchema(
                            name: 'unit', description: 'The unit of the ingredient'
                        ),
                    ],
                    requiredFields: ['name', 'quantity', 'unit']
                )
            ),
        ], requiredFields: ['title']
    );

    $response = Prism::structured()
        ->withSchema($schema)
        ->using(Provider::Gemini, 'gemini-1.5-flash')
        ->withPrompt('A popular recipe with ingredients')
        ->generate();

    expect($response->structured)->toBeArray();

    expect($response->structured)->toHaveKeys([
        'recipe_name',
        'ingredients',
    ]);
    expect($response->structured['recipe_name'])->toBeString();
    expect($response->structured['ingredients'])->toBeArray();
    expect($response->structured['ingredients'][0])->toHaveKeys([
        'name',
        'quantity',
        'unit',
    ]);
});
