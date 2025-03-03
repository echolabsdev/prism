<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Schema\BooleanSchema;
use EchoLabs\Prism\Schema\NumberSchema;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\ValueObjects\Messages\Support\Document;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

it('returns structured output', function (): void {
    FixtureResponse::fakeResponseSequence('*', 'gemini/generate-structured');

    $schema = new ObjectSchema(
        'output',
        'the output object',
        [
            new StringSchema('weather', 'The weather forecast'),
            new StringSchema('game_time', 'The tigers game time'),
            new BooleanSchema('coat_required', 'whether a coat is required'),
        ],
        ['weather', 'game_time', 'coat_required']
    );

    $response = Prism::structured()
        ->using(Provider::Gemini, 'gemini-1.5-flash-002')
        ->withSchema($schema)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    expect($response->structured)->toBeArray();
    expect($response->structured)->toHaveKeys([
        'weather',
        'game_time',
        'coat_required',
    ]);
    expect($response->structured['weather'])->toBeString();
    expect($response->structured['game_time'])->toBeString();
    expect($response->structured['coat_required'])->toBeBool();

    expect($response->usage->promptTokens)->toBe(43);
    expect($response->usage->completionTokens)->toBe(27);
});

it('can use a cache object with a structured request', function (): void {
    $file = file_get_contents('https://eur-lex.europa.eu/LexUriServ/LexUriServ.do?uri=CELEX:12012E/TXT:en:PDF');

    FixtureResponse::fakeResponseSequence('*', 'gemini/use-cache-with-structured');

    /** @var Gemini */
    $provider = Prism::provider(Provider::Gemini);

    $object = $provider->cache(
        model: 'gemini-1.5-flash-002',
        messages: [
            new UserMessage('', [
                Document::fromBase64(base64_encode($file), 'application/pdf'),
            ]),
        ],
        systemPrompts: [
            new SystemMessage('You are a legal analyst.'),
        ],
        ttl: 30
    );

    $response = Prism::structured()
        ->using(Provider::Gemini, 'gemini-1.5-flash-002')
        ->withSchema(new ObjectSchema('answer', '', [
            new StringSchema('legal_jurisdiction', 'Which legal jurisdiction is this document from?'),
            new StringSchema('legislation_type', 'What type of legislation is this (e.g. a treaty, a regulation, an act, a directive, etc.)?'),
            new NumberSchema('article_count', 'How many articles does the main body of the legislation contain?'),
        ]))
        ->withProviderMeta(Provider::Gemini, ['cachedContentName' => $object->name])
        ->withPrompt('Summarise this document using the properties and descriptions defined in the schema.')
        ->generate();

    Http::assertSentInOrder([
        fn (Request $request): bool => true,
        fn (Request $request): bool => $request->data()['cachedContent'] === $object->name,
    ]);

    expect($response->structured)->toBeArray();
    expect($response->structured)->toHaveKeys([
        'legal_jurisdiction',
        'legislation_type',
        'article_count',
    ]);

    expect($response->structured['article_count'])->toBe(358);
    expect($response->structured['legal_jurisdiction'])->toBe('European Union');
    expect($response->structured['legislation_type'])->toBe('Treaty');
});
