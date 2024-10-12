<?php

declare(strict_types=1);

namespace Tests\Drivers;

use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Providers\Anthropic\Anthropic;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.anthropic.api_key', env('ANTHROPIC_API_KEY', 'sk-1234'));
    config()->set('prism.providers.anthropic.api_version', '2023-06-01');
});

it('can generate text with a prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-a-prompt');

    $response = Prism::text()
        ->using(Anthropic::make('claude-3-5-sonnet-20240620'))
        ->withPrompt('Who are you?')();

    expect($response->usage->promptTokens)->toBe(11);
    expect($response->usage->completionTokens)->toBe(55);
    expect($response->response['id'])->toBe('msg_01X2Qk7LtNEh4HB9xpYU57XU');
    expect($response->response['model'])->toBe('claude-3-5-sonnet-20240620');
    expect($response->text)->toBe(
        "I am an AI assistant created by Anthropic to be helpful, harmless, and honest. I don't have a physical form or avatar - I'm a language model trained to engage in conversation and help with tasks. How can I assist you today?"
    );
});

it('can generate text with a system prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-system-prompt');

    $response = Prism::text()
        ->using(Anthropic::make('claude-3-5-sonnet-20240620'))
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withPrompt('Who are you?')();

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
            ->withParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 75° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $response = Prism::text()
        ->using(Anthropic::make('claude-3-5-sonnet-20240620'))
        ->withTools($tools)
        ->withMaxSteps(3)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')();

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
