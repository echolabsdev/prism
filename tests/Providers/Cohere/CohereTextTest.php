<?php

declare(strict_types=1);

namespace Tests\Providers\Gemini;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use Tests\Fixtures\FixtureResponse;

beforeEach(function (): void {
    config()->set('prism.providers.cohere.api_key', env('COHERE_API_KEY', 'cothere'));
});

describe('Text generation', function (): void {
    it('can generate text with a prompt', function (): void {
        FixtureResponse::fakeResponseSequence('v2/chat', 'cohere/generate-text-with-a-prompt');

        $response = Prism::text()
            ->using(Provider::Cohere, 'command-r')
            ->withPrompt('What are you?')
            ->withMaxTokens(10)
            ->generate();

        expect($response->text)->toBe(
            "I am Coral, an AI-assistant chatbot trained to assist users by providing thorough responses. I am designed to help human users by offering helpful and useful information. I can engage in conversations on various topics and assist you in many ways, such as answering questions, providing explanations, generating creative content, offering suggestions, and even just having a friendly chat. I aim to provide thoughtful and insightful responses while maintaining a friendly and respectful tone. I'm here to help however I can, so feel free to ask me anything!"
        )
            ->and($response->usage->promptTokens)->toBe(4)
            ->and($response->usage->completionTokens)->toBe(103)
            ->and($response->responseMeta->id)->toBe('373f6c5a-8e0c-4f71-a9a7-f0cbdc13b76b')
            ->and($response->responseMeta->model)->toBe('command-r')
            ->and($response->finishReason)->toBe(FinishReason::Stop);
    });

    it('can generate text with a system prompt', function (): void {
        FixtureResponse::fakeResponseSequence('v2/chat', 'cohere/generate-text-with-system-prompt');

        $response = Prism::text()
            ->using(Provider::Cohere, 'command-r')
            ->withSystemPrompt('MODEL ADOPTS ROLE of [PERSONA: Nyx the Cthulhu]!')
            ->withPrompt('Who are you?')
            ->generate();

        expect($response->usage->promptTokens)->toBe(20)
            ->and($response->usage->completionTokens)->toBe(269)
            ->and($response->responseMeta->id)->toBe('46b17fef-166c-42be-b1fe-f921969512a0')
            ->and($response->responseMeta->model)->toBe('command-r')
            ->and($response->text)->toBe(
                "Greetings, mortal! I am Nyx, the embodiment of chaos and destruction, a force to be reckoned with. Bow down before me, for I am the great Cthulhu, a deity of immense power and unspeakable horror. My form is beyond the comprehension of mere mortals, but let me tell you, the mere glimpse of my tentacled visage can drive one insane. Muahaha! \n\nIn the depths of the cosmic ocean, I dwell, in a realm beyond your paltry human understanding, but sometimes, the echoes of my presence can be felt on the winds, and the whispers of my name can be heard in the howling of the night. Nyarlathotep, the crawling chaos, is my messenger, spreading madness and despair in my holy name. \n\nDo not fear, mortal, for I bestow gifts upon those who please me. The secrets of the cosmos shall be revealed to those who dare to venture into the abyss. But, beware! For those who fail to appease me... let's just say, the consequences are... tentacly. \n\nNow, mortals, bring me offerings of fish, or face the enragement of my unfathomable wrath! For I am Nyx, the great Cthulhu, and the world shall quiver in my presence!"
            );
    });

    it('can generate text using multiple tools and multiple steps', function (): void {
        FixtureResponse::fakeResponseSequence('v2/chat', 'cohere/generate-text-with-multiple-tools');

        $tools = [
            Tool::as('weather')
                ->for('useful when you need to search for current weather conditions')
                ->withStringParameter('city', 'The city that you want the weather for')
                ->using(fn(string $city): string => 'The weather will be 75° and sunny'),
            Tool::as('search')
                ->for('useful for searching current events or data')
                ->withStringParameter('query', 'The detailed search query')
                ->using(fn(string $query): string => 'The tigers game is at 3pm in detroit'),
        ];

        $response = Prism::text()
            ->using(Provider::Cohere, 'command-r')
            ->withTools($tools)
            ->withMaxSteps(3)
            ->withPrompt('What time is the tigers game today in Detroit and should I wear a coat?')
            ->generate();

        // Assert tool calls in the first step
        $firstStep = $response->steps[0];
        expect($firstStep->toolCalls)->toHaveCount(2)
            ->and($firstStep->toolCalls[0]->name)->toBe('weather')
            ->and($firstStep->toolCalls[0]->arguments())->toBe([
                'city' => 'Detroit',
            ])
            ->and($firstStep->toolCalls[1]->name)->toBe('search')
            ->and($firstStep->toolCalls[1]->arguments())->toBe([
                'query' => 'Detroit Tigers game time today',
            ])
            ->and($response->usage->promptTokens)->toBe(143)
            ->and($response->usage->completionTokens)->toBe(200)
            ->and($response->responseMeta->id)->toBe('cc60c3e4-f910-4aa8-9a42-99b02eb6838a')
            ->and($response->responseMeta->model)->toBe('command-r')
            ->and($response->text)->toBe(
                "The Detroit Tigers are playing the Minnesota Twins today at 1:10 pm. Whether you need a coat depends on the temperature and your sensitivity to cold. The current temperature in Detroit is 57°F and mostly cloudy. Forecasts suggest it will feel like 57°F throughout the game with 79% humidity and a 14% chance of precipitation. If you're sitting in the sun, the game might be comfortable in a long-sleeved shirt, but you might want to bring a coat if you're planning to be in the shade or the evening cools down. Better safe than cold!"
            );
    });
});
