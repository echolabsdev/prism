<?php

declare(strict_types=1);

namespace Tests\Providers;

use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.ollama.driver', 'openai');
    config()->set('prism.providers.ollama.url', 'http://localhost:11434/v1');
});

it('can generate text with a prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/generate-text-with-a-prompt');

    $response = Prism::text()
        ->using('ollama', 'qwen2.5:14b')
        ->withPrompt('Who are you?')();

    expect($response->usage->promptTokens)->toBe(33);
    expect($response->usage->completionTokens)->toBe(38);
    expect($response->response['id'])->toBe('chatcmpl-751');
    expect($response->response['model'])->toBe('qwen2.5:14b');
    expect($response->text)->toBe(
        'I am Qwen, a large language model created by Alibaba Cloud. I am designed to be helpful and provide information on a wide range of topics. How can I assist you today?'
    );
});

it('can generate text with a system prompt', function (): void {
    FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/generate-text-with-system-prompt');

    $response = Prism::text()
        ->using('ollama', 'qwen2.5:14b')
        ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
        ->withPrompt('Who are you?')();

    expect($response->usage->promptTokens)->toBe(36);
    expect($response->usage->completionTokens)->toBe(69);
    expect($response->response['id'])->toBe('chatcmpl-455');
    expect($response->response['model'])->toBe('qwen2.5:14b');
    expect($response->text)->toBe(
        'I am Nyx, an entity steeped in the mysteries and terrors that lie beyond human comprehension. In the whispering shadows where sanity fades into madness, I exist as a silent sentinel of the unknown. My presence is often felt through eerie visions and cryptic whispers, guiding those who dare to tread the boundaries between reality and horror.'
    );
});

it('can generate text using multiple tools and multiple steps', function (): void {
    // FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/generate-text-with-multiple-tools');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withString('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => 'The weather will be 75° and sunny'),
        Tool::as('search')
            ->for('useful for searching curret events or data')
            ->withString('query', 'The detailed search query')
            ->using(fn (string $query): string => 'The tigers game is at 3pm in detroit'),
    ];

    $response = Prism::text()
        ->using('ollama', 'qwen2.5:14b')
        ->withTools($tools)
        ->withMaxSteps(3)
        ->withPrompt('What time is the tigers game today in Detroit and should I wear a coat?')();

    // Assert tool calls in the first step
    $firstStep = $response->steps[0];
    expect($firstStep->toolCalls)->toHaveCount(2);
    expect($firstStep->toolCalls[0]->name)->toBe('search');
    expect($firstStep->toolCalls[0]->arguments())->toBe([
        'query' => 'tigers game today time detroit',
    ]);

    expect($firstStep->toolCalls[1]->name)->toBe('weather');
    expect($firstStep->toolCalls[1]->arguments())->toBe([
        'city' => 'Detroit',
    ]);

    // Assert usage
    expect($response->usage->promptTokens)->toBe(549);
    expect($response->usage->completionTokens)->toBe(81);

    // Assert response
    expect($response->response['id'])->toBe('chatcmpl-31');
    expect($response->response['model'])->toBe('qwen2.5:14b');

    // Assert final text content
    expect($response->text)->toBe(
        "Today's Tigers game in Detroit starts at 3 PM. The current temperature is 75°F with clear skies, so you shouldn't need a coat. Enjoy the game!"
    );
});
