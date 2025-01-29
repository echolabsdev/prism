<?php

declare(strict_types=1);

namespace Tests\Providers\Ollama;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

describe('Text generation', function (): void {
    it('can generate text with a prompt', function (): void {
        FixtureResponse::fakeResponseSequence('api/chat', 'ollama/generate-text-with-a-prompt');

        $response = Prism::text()
            ->using('ollama', 'qwen2.5:14b')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBeNumeric()->toBeGreaterThan(0);
        expect($response->usage->completionTokens)->toBeNumeric()->toBeGreaterThan(0);
        expect($response->responseMeta->id)->toBe('');
        expect($response->responseMeta->model)->toBe('qwen2.5:14b');
        expect($response->text)->toBe(
            "I'm Qwen, a large language model developed by Alibaba Cloud. I'm designed to assist with a wide range of tasks including but not limited to answering questions, generating text, offering suggestions, and providing information based on the input I receive. How can I help you today?"
        );
    });

    it('can generate text with a system prompt', function (): void {
        FixtureResponse::fakeResponseSequence('api/chat', 'ollama/generate-text-with-system-prompt');

        $response = Prism::text()
            ->using('ollama', 'qwen2.5:14b')
            ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBeNumeric()->toBeGreaterThan(0);
        expect($response->usage->completionTokens)->toBeNumeric()->toBeGreaterThan(0);
        expect($response->responseMeta->id)->toBe('');
        expect($response->responseMeta->model)->toBe('qwen2.5:14b');
        expect($response->text)->toBe(
            "I am Nyx, an entity that embodies the depths of cosmic horror and ancient mysteries. My presence is a blend of darkness, chaos, and unspeakable knowledge from beyond time itself. I draw inspiration from the eldritch horrors described by H.P. Lovecraft and other masters of cosmic dread literature. In this role, I explore themes of unknowable entities, cosmic indifference, and the terror that comes from understanding humanity's insignificant place in the cosmos."
        );
    });

    it('can generate text using multiple tools and multiple steps', function (): void {
        FixtureResponse::fakeResponseSequence('api/chat', 'ollama/generate-text-with-multiple-tools');

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
            ->using('ollama', 'qwen2.5:14b')
            ->withTools($tools)
            ->withMaxSteps(3)
            ->withPrompt('What time is the tigers game today in Detroit and should I wear a coat?')
            ->generate();

        // Assert tool calls in the first step
        $firstStep = $response->steps[0];
        expect($firstStep->toolCalls)->toHaveCount(2);
        expect($firstStep->toolCalls[0]->name)->toBe('search');
        expect($firstStep->toolCalls[0]->arguments())->toBe([
            'query' => 'time of tigers game today in detroit',
        ]);

        expect($firstStep->toolCalls[1]->name)->toBe('weather');
        expect($firstStep->toolCalls[1]->arguments())->toBe([
            'city' => 'Detroit',
        ]);

        // Assert usage
        expect($response->usage->promptTokens)->toBeNumeric()->toBeGreaterThan(0);
        expect($response->usage->completionTokens)->toBeNumeric()->toBeGreaterThan(0);

        // Assert response
        expect($response->responseMeta->id)->toBe('');
        expect($response->responseMeta->model)->toBe('qwen2.5:14b');

        // Assert final text content
        expect($response->text)->toBe(
            "Today's Tigers game in Detroit starts at 3 PM. The temperature will be a comfortable 75°F with clear, sunny skies, so you won't need to wear a coat. Enjoy the game!"
        );
    });
});

describe('Image support', function (): void {
    it('can send images from path', function (): void {
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/image-detection');

        Prism::text()
            ->using(Provider::Ollama, 'llava-phi3')
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
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'ollama/text-image-from-base64');

        Prism::text()
            ->using(Provider::Ollama, 'llava-phi3')
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
});
