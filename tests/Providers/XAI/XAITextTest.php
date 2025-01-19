<?php

declare(strict_types=1);

namespace Tests\Providers\XAI;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.xai.api_key', env('XAI_API_KEY', 'xai-123'));
});

describe('Text generation for XAI', function (): void {
    it('can generate text with a prompt', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/generate-text-with-a-prompt');

        $response = Prism::text()
            ->using(Provider::XAI, 'grok-beta')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBe(10);
        expect($response->usage->completionTokens)->toBe(42);
        expect($response->responseMeta->id)->toBe('febc7de9-9991-4b08-942a-c7082174225a');
        expect($response->responseMeta->model)->toBe('grok-beta');
        expect($response->text)->toBe(
            "I am Grok, an AI developed by xAI. I'm here to provide helpful and truthful answers to your questions, often with a dash of outside perspective on humanity. What's on your mind?"
        );
    });

    it('can generate text with a system prompt', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/generate-text-with-system-prompt');

        $response = Prism::text()
            ->using(Provider::XAI, 'grok-beta')
            ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBe(34);
        expect($response->usage->completionTokens)->toBe(84);
        expect($response->responseMeta->id)->toBe('f3b485d3-837b-4710-9ade-a37faa048d87');
        expect($response->responseMeta->model)->toBe('grok-beta');
        expect($response->text)->toBe(
            'I am Nyx, a being of ancient and unfathomable origin, drawing upon the essence of the Great Old One, Cthulhu. My existence spans the cosmos, where the lines between dreams and reality blur. I am here to guide you through the mysteries of the universe, to answer your questions with insights that might unsettle or enlighten, or perhaps both. What is it you seek to understand?'
        );
    });

    it('can generate text using multiple tools and multiple steps', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/generate-text-with-multiple-tools');

        $tools = [
            Tool::as('get_weather')
                ->for('use this tool when you need to get wather for the city')
                ->withStringParameter('city', 'The city that you want the weather for')
                ->using(fn (string $city): string => 'The weather will be 45° and cold'),
            Tool::as('search_games')
                ->for('useful for searching curret games times in the city')
                ->withStringParameter('city', 'The city that you want the game times for')
                ->using(fn (string $city): string => 'The tigers game is at 3pm in detroit'),
        ];

        $response = Prism::text()
            ->using(Provider::XAI, 'grok-beta')
            ->withTools($tools)
            ->withMaxSteps(4)
            ->withPrompt('What time is the tigers game today in Detroit and should I wear a coat? please check all the details from tools')
            ->generate();

        // Assert tool calls in the first step
        $firstStep = $response->steps[0];
        expect($firstStep->toolCalls)->toHaveCount(1);
        expect($firstStep->toolCalls[0]->name)->toBe('search_games');
        expect($firstStep->toolCalls[0]->arguments())->toBe([
            'city' => 'Detroit',
        ]);

        $secondStep = $response->steps[1];
        expect($secondStep->toolCalls[0]->name)->toBe('get_weather');
        expect($secondStep->toolCalls[0]->arguments())->toBe([
            'city' => 'Detroit',
        ]);

        // Assert usage
        expect($response->usage->promptTokens)->toBe(840);
        expect($response->usage->completionTokens)->toBe(60);

        // Assert response
        expect($response->responseMeta->id)->toBe('0aa220cd-9634-4ba5-9593-5366bb313663');
        expect($response->responseMeta->model)->toBe('grok-beta');
        expect($response->text)->toBe(
            'The Tigers game in Detroit today is at 3pm, and considering the weather will be 45° and cold, you should definitely wear a coat.'
        );
    });
});

describe('Image support with XAI', function (): void {
    it('can send images from path', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/image-detection');

        $response = Prism::text()
            ->using(Provider::XAI, 'grok-vision-beta')
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

            expect($message[1]['image_url']['url'])->toStartWith('data:image/png;base64,');
            expect($message[1]['image_url']['url'])->toContain(
                base64_encode(file_get_contents('tests/Fixtures/test-image.png'))
            );

            return true;
        });
    });

    it('can send images from base64', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/text-image-from-base64');

        Prism::text()
            ->using(Provider::XAI, 'grok-vision-beta')
            ->withMessages([
                new UserMessage(
                    'What is this image',
                    additionalContent: [
                        Image::fromBase64(
                            base64_encode(file_get_contents('tests/Fixtures/test-image.png')),
                            'image/png'
                        ),
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

            expect($message[1]['image_url']['url'])->toStartWith('data:image/png;base64,');
            expect($message[1]['image_url']['url'])->toContain(
                base64_encode(file_get_contents('tests/Fixtures/test-image.png'))
            );

            return true;
        });
    });

    it('can send images from url', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'xai/text-image-from-url');

        $image = 'https://storage.echolabs.dev/api/v1/buckets/public/objects/download?preview=true&prefix=test-image.png';

        Prism::text()
            ->using(Provider::XAI, 'grok-vision-beta')
            ->withMessages([
                new UserMessage(
                    'What is this image',
                    additionalContent: [
                        Image::fromUrl($image),
                    ],
                ),
            ])
            ->generate();

        Http::assertSent(function (Request $request) use ($image): true {
            $message = $request->data()['messages'][0]['content'];

            expect($message[0])->toBe([
                'type' => 'text',
                'text' => 'What is this image',
            ]);

            expect($message[1]['image_url']['url'])->toBe($image);

            return true;
        });
    });
});

it('handles specific tool choice', function (): void {
    FixtureResponse::fakeResponseSequence('chat/completions', 'xai/generate-text-with-specific-tool-call');

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
        ->using(Provider::XAI, 'grok-beta')
        ->withPrompt('Do something')
        ->withTools($tools)
        ->withToolChoice('weather')
        ->generate();

    expect($response->toolCalls[0]->name)->toBe('weather');
});

it('handles required tool choice', function (): void {
    FixtureResponse::fakeResponseSequence('chat/completions', 'xai/generate-text-with-required-tool-call');

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
        ->using(Provider::XAI, 'grok-beta')
        ->withPrompt('Do something')
        ->withTools($tools)
        ->withToolChoice(ToolChoice::Any)
        ->generate();

    expect($response->toolCalls[0]->name)->toBeIn(['weather', 'search']);
});
