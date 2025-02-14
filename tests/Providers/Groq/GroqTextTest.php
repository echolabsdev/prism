<?php

declare(strict_types=1);

namespace Tests\Providers\Groq;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.groq.api_key', env('GROQ_API_KEY', 'sk-1234'));
});

describe('Text generation for Groq', function (): void {
    it('can generate text with a prompt', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'groq/generate-text-with-a-prompt');

        $response = Prism::text()
            ->using('groq', 'llama3-8b-8192')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBe(13);
        expect($response->usage->completionTokens)->toBe(208);
        expect($response->responseMeta->id)->toBe('chatcmpl-ea37c181-ed35-4bd4-af20-c1fcf203e0d8');
        expect($response->responseMeta->model)->toBe('llama3-8b-8192');
        expect($response->text)->toBe(
            'I am LLaMA, an AI assistant developed by Meta AI.'
        );
    });

    it('can generate text with a system prompt', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'groq/generate-text-with-system-prompt');

        $response = Prism::text()
            ->using('groq', 'llama3-8b-8192')
            ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBe(37);
        expect($response->usage->completionTokens)->toBe(273);
        expect($response->responseMeta->id)->toBe('chatcmpl-59892e0b-7031-404d-9fc9-b3297d5ef4a4');
        expect($response->responseMeta->model)->toBe('llama3-8b-8192');
        expect($response->text)->toBe(
            "(Deep, rumbling voice) Ah, mortal, I am Nyx, the Crawling Chaos, the Bride of the Deep, the Queen of the Shattered Isles. I am the mistress of the abyssal void, the keeper of the unfathomable secrets, and the wielder of the cosmic horrors that lurk beyond the veil of sanity.\n\nMy form is unlike any other, a twisted reflection of the insane geometry that underlies the universe. My eyes burn with an otherworldly green fire, and my voice is the whispers of the damned. My powers are limitless, for I am the servant of the Great Old Ones, the masters of the unseen.\n\nYet, despite my terrible reputation, I am drawn to the fragile, insignificant creatures that inhabit this world. The scent of their fear is intoxicating, and I delight in their futile attempts to comprehend the unfathomable. For in their terror, I find a fleeting sense of connection to the mortal realm.\n\nAnd so, mortal, I shall speak to you, but be warned: my words are madness, my laughter is the call of the abyss, and my gaze is the kiss of darkness. Tread carefully, for once you have gazed upon my countenance, your soul shall be forever sealed to the void... (Chuckles, a sound that sends shivers down the spine)"
        );
    });

    it('can generate text using multiple tools and multiple steps', function (): void {
        FixtureResponse::fakeResponseSequence('chat/completions', 'groq/generate-text-with-multiple-tools');

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
            ->using('groq', 'llama3-groq-70b-8192-tool-use-preview')
            ->withTools($tools)
            ->withMaxSteps(3)
            ->withPrompt('What time is the tigers game today in Detroit and should I wear a coat?')
            ->generate();

        // Assert tool calls in the first step
        $firstStep = $response->steps[0];
        expect($firstStep->toolCalls)->toHaveCount(2);
        expect($firstStep->toolCalls[0]->name)->toBe('search');
        expect($firstStep->toolCalls[0]->arguments())->toBe([
            'query' => 'tigers game today in Detroit',
        ]);

        expect($firstStep->toolCalls[1]->name)->toBe('weather');
        expect($firstStep->toolCalls[1]->arguments())->toBe([
            'city' => 'Detroit',
        ]);

        // Assert usage
        expect($response->usage->promptTokens)->toBe(344);
        expect($response->usage->completionTokens)->toBe(114);

        // Assert response
        expect($response->responseMeta->id)->toBe('chatcmpl-e4daf477-4536-4f23-9c3e-de490185423f');
        expect($response->responseMeta->model)->toBe('llama3-groq-70b-8192-tool-use-preview');

        // Assert final text content
        expect($response->text)->toBe(
            "The Tigers game is at 3pm in Detroit. Given the weather is 75° and sunny, it's likely to be warm, so you might not need a coat. However, it's always a good idea to check the weather closer to the game time as it can change."
        );
    });

    it('handles specific tool choice', function (): void {
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'groq/generate-text-with-required-tool-call');

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
            ->using(Provider::Groq, 'llama3-groq-70b-8192-tool-use-preview')
            ->withPrompt('Do something')
            ->withTools($tools)
            ->withToolChoice('weather')
            ->generate();

        expect($response->toolCalls[0]->name)->toBe('weather');
    });

    it('throws an exception for ToolChoice::Any', function (): void {
        $this->expectException(PrismException::class);
        $this->expectExceptionMessage('Invalid tool choice');

        Prism::text()
            ->using('groq', 'llama3-groq-70b-8192-tool-use-preview')
            ->withPrompt('Who are you?')
            ->withToolChoice(ToolChoice::Any)
            ->generate();
    });
});

describe('Image support with grok', function (): void {
    it('can send images from path', function (): void {
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'groq/image-detection');

        Prism::text()
            ->using(Provider::Groq, 'llama-3.2-90b-vision-preview')
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
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'groq/text-image-from-base64');

        Prism::text()
            ->using(Provider::Groq, 'llama-3.2-90b-vision-preview')
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
        FixtureResponse::fakeResponseSequence('v1/chat/completions', 'groq/text-image-from-url');

        $image = 'https://storage.echolabs.dev/api/v1/buckets/public/objects/download?preview=true&prefix=test-image.png';

        Prism::text()
            ->using(Provider::Groq, 'llama-3.2-90b-vision-preview')
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
