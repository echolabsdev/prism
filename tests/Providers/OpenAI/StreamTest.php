<?php

declare(strict_types=1);

namespace Tests\Providers\OpenAI;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PrismPHP\Prism\Facades\Tool;
use PrismPHP\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.openai.api_key', env('OPENAI_API_KEY', 'fake-key'));
});

it('can generate text with a basic stream', function (): void {
    FixtureResponse::fakeStreamResponses('v1/chat/completions', 'openai/stream-basic-text');

    $response = Prism::stream()
        ->using('openai', 'gpt-4')
        ->withPrompt('Who are you?')
        ->generate();

    $text = '';
    $chunks = [];

    foreach ($response as $chunk) {
        $chunks[] = $chunk;
        $text .= $chunk->text;
    }

    expect($chunks)->not->toBeEmpty();
    expect($text)->not->toBeEmpty();

    // Verify the HTTP request
    Http::assertSent(function (Request $request): bool {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $body['stream'] === true;
    });
});

it('can generate text using tools with streaming', function (): void {
    FixtureResponse::fakeStreamResponses('v1/chat/completions', 'openai/stream-with-tools');

    $tools = [
        Tool::as('weather')
            ->for('useful when you need to search for current weather conditions')
            ->withStringParameter('city', 'The city that you want the weather for')
            ->using(fn (string $city): string => "The weather will be 75° and sunny in {$city}"),

        Tool::as('search')
            ->for('useful for searching current events or data')
            ->withStringParameter('query', 'The detailed search query')
            ->using(fn (string $query): string => "Search results for: {$query}"),
    ];

    $response = Prism::stream()
        ->using('openai', 'gpt-4o')
        ->withTools($tools)
        ->withMaxSteps(3)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    $text = '';
    $chunks = [];
    $toolCallFound = false;
    $toolResults = [];

    foreach ($response as $chunk) {
        $chunks[] = $chunk;

        if (! empty($chunk->toolCalls)) {
            $toolCallFound = true;
            expect($chunk->toolCalls[0]->name)->not->toBeEmpty();
            expect($chunk->toolCalls[0]->arguments())->toBeArray();
        }

        if (! empty($chunk->toolResults)) {
            $toolResults = array_merge($toolResults, $chunk->toolResults);
        }

        $text .= $chunk->text;
    }

    expect($chunks)->not->toBeEmpty();
    expect($toolCallFound)->toBeTrue('Expected to find at least one tool call in the stream');

    // Verify the HTTP request
    Http::assertSent(function (Request $request): bool {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && isset($body['tools'])
            && $body['stream'] === true;
    });
});

it('can process a complete conversation with multiple tool calls', function (): void {
    FixtureResponse::fakeStreamResponses('v1/chat/completions', 'openai/stream-multi-tool-conversation');

    $tools = [
        Tool::as('weather')
            ->for('Get weather information')
            ->withStringParameter('city', 'City name')
            ->using(fn (string $city): string => "The weather in {$city} is 75° and sunny."),

        Tool::as('search')
            ->for('Search for information')
            ->withStringParameter('query', 'The search query')
            ->using(fn (string $query): string => 'Tigers game is at 3pm in Detroit today.'),
    ];

    $response = Prism::stream()
        ->using('openai', 'gpt-4o')
        ->withTools($tools)
        ->withMaxSteps(5) // Allow multiple tool call rounds
        ->withPrompt('What time is the Tigers game today and should I wear a coat in Detroit?')
        ->generate();

    $fullResponse = '';
    $toolCallCount = 0;

    foreach ($response as $chunk) {
        if (! empty($chunk->toolCalls)) {
            $toolCallCount++;
        }
        $fullResponse .= $chunk->text;
    }

    expect($toolCallCount)->toBeGreaterThanOrEqual(1);
    expect($fullResponse)->not->toBeEmpty();

    // Verify we made multiple requests for a conversation with tool calls
    Http::assertSentCount(3);
});
