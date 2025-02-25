<?php

declare(strict_types=1);

use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.openai.api_key', env('OPENAI_API_KEY'));
});

it('can generate text with a prompt', function (): void {
    // FixtureResponse::fakeResponseSequence('v1/chat/completions', 'openai/generate-text-with-a-prompt');

    $response = Prism::stream()
        ->using('openai', 'gpt-4')
        ->withPrompt('Who are you?')
        ->generate();

    foreach ($response as $chunk) {
        ray('chunk', $chunk);
    }

    dd('response end');
});

it('can generate text using multiple tools and multiple steps', function (): void {
    // FixtureResponse::fakeResponseSequence('v1/chat/completions', 'openai/generate-text-with-multiple-tools');

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

    $response = Prism::stream()
        ->using('openai', 'gpt-4o')
        ->withTools($tools)
        ->withMaxSteps(3)
        ->withPrompt('What time is the tigers game today and should I wear a coat?')
        ->generate();

    $text = '';

    foreach ($response as $chunk) {
        $text .= $chunk->text;
    }

    dd('response: '.$text);
});
