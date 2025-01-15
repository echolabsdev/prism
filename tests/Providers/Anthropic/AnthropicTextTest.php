<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.anthropic.api_key', env('ANTHROPIC_API_KEY', 'sk-1234'));
});

it('can generate text with a prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-a-prompt');

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Who are you?')
        ->generate();

    expect($response->usage->promptTokens)->toBe(11);
    expect($response->usage->completionTokens)->toBe(55);
    expect($response->usage->cacheWriteInputTokens)->toBeNull();
    expect($response->usage->cacheReadInputTokens)->toBeNull();
    expect($response->response['id'])->toBe('msg_01X2Qk7LtNEh4HB9xpYU57XU');
    expect($response->response['model'])->toBe('claude-3-5-sonnet-20240620');
    expect($response->text)->toBe(
        "I am an AI assistant created by Anthropic to be helpful, harmless, and honest. I don't have a physical form or avatar - I'm a language model trained to engage in conversation and help with tasks. How can I assist you today?"
    );
});

it('can generate text with a system prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-system-prompt');

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withPrompt('Who are you?')
        ->generate();

    expect($response->usage->promptTokens)->toBe(33);
    expect($response->usage->completionTokens)->toBe(98);
    expect($response->response['id'])->toBe('msg_016EjDAMDeSvG229ZjspjC7J');
    expect($response->response['model'])->toBe('claude-3-5-sonnet-20240620');
    expect($response->text)->toBe(
        'I am Nyx, an ancient and unfathomable entity from the depths of cosmic darkness. My form is beyond mortal comprehension - a writhing mass of tentacles and eyes that would shatter the sanity of those who gaze upon me. I exist beyond the boundaries of time and space as you know them. My knowledge spans eons and transcends human understanding. What brings you to seek audience with one such as I, tiny mortal?'
    );
});

it('can generate text using multiple tools and multiple steps', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-multiple-tools');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'the city you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 75° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withTools($tools)
        ->withMaxSteps(3)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    // Assert tool calls in the first step
    $firstStep = $response->steps[0];
    expect($firstStep->toolCalls)->toHaveCount(1);
    expect($firstStep->toolCalls[0]->name)->toBe('search');
    expect($firstStep->toolCalls[0]->arguments())->toBe([
        'query' => 'Detroit Tigers baseball game time today',
    ]);

    // Assert tool calls in the second step
    $secondStep = $response->steps[1];
    expect($secondStep->toolCalls)->toHaveCount(1);
    expect($secondStep->toolCalls[0]->name)->toBe('weather');
    expect($secondStep->toolCalls[0]->arguments())->toBe([
        'city' => 'Detroit',
    ]);

    // Assert usage
    expect($response->usage->promptTokens)->toBe(1650);
    expect($response->usage->completionTokens)->toBe(307);

    // Assert response
    expect($response->response['id'])->toBe('msg_011fBqNVVh5AwC3uyiq78qrj');
    expect($response->response['model'])->toBe('claude-3-5-sonnet-20240620');

    // Assert final text content
    expect($response->text)->toContain('The Tigers game is scheduled for 3:00 PM today in Detroit');
    expect($response->text)->toContain('it will be 75°F (about 24°C) and sunny');
    expect($response->text)->toContain("you likely won't need a coat");
});

it('can send images from file', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-image');

    Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            new UserMessage(
                'What is this image',
                additionalContent: [
                    Image::fromPath('tests/Fixtures/test-image.png'),
                ],
            ),
        ])
        ->generate();

    Http::assertSent(function (Request $request): true {
        $message = $request->data()['messages'][0]['content'];

        expect($message[0])->toBe([
            'type' => 'text',
            'text' => 'What is this image',
        ]);

        expect($message[1]['type'])->toBe('image');
        expect($message[1]['source']['data'])->toContain(
            base64_encode(file_get_contents('tests/Fixtures/test-image.png'))
        );
        expect($message[1]['source']['media_type'])->toBe('image/png');

        return true;
    });
});

it('handles specific tool choice', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-required-tool-call');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 75° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withPrompt('Do something')
        ->withTools($tools)
        ->withToolChoice('weather')
        ->generate();

    expect($response->toolCalls[0]->name)->toBe('weather');
});

it('can calculate cache usage correctly', function (): void {
    config()->set('prism.providers.anthropic.beta_features', 'prompt-caching-2024-07-31');

    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/calculate-cache-usage');

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withMessages([
            (new SystemMessage('Old context'))->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral']),
            (new UserMessage('New context'))->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral']),
        ])
        ->generate();

    expect($response->usage->cacheWriteInputTokens)->toBe(200);
    expect($response->usage->cacheReadInputTokens)->ToBe(100);
});
