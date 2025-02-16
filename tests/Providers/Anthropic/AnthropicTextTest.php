<?php

declare(strict_types=1);

namespace Tests\Providers\Anthropic;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Facades\Tool;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Providers\Anthropic\Handlers\Text;
use EchoLabs\Prism\Providers\Anthropic\ValueObjects\MessagePartWithCitations;
use EchoLabs\Prism\ValueObjects\Messages\Support\Document;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderRateLimit;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
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
    expect($response->responseMeta->id)->toBe('msg_01X2Qk7LtNEh4HB9xpYU57XU');
    expect($response->responseMeta->model)->toBe('claude-3-5-sonnet-20240620');
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
    expect($response->responseMeta->id)->toBe('msg_016EjDAMDeSvG229ZjspjC7J');
    expect($response->responseMeta->model)->toBe('claude-3-5-sonnet-20240620');
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
    expect($response->responseMeta->id)->toBe('msg_011fBqNVVh5AwC3uyiq78qrj');
    expect($response->responseMeta->model)->toBe('claude-3-5-sonnet-20240620');

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
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/calculate-cache-usage');

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withMessages([
            (new UserMessage('New context'))->withProviderMeta(Provider::Anthropic, ['cacheType' => 'ephemeral']),
        ])
        ->generate();

    expect($response->usage->cacheWriteInputTokens)->toBe(200);
    expect($response->usage->cacheReadInputTokens)->ToBe(100);
});

it('adds rate limit data to the responseMeta', function (): void {
    $requests_reset = Carbon::now()->addSeconds(30);

    FixtureResponse::fakeResponseSequence(
        'v1/messages',
        'anthropic/generate-text-with-a-prompt',
        [
            'anthropic-ratelimit-requests-limit' => 1000,
            'anthropic-ratelimit-requests-remaining' => 500,
            'anthropic-ratelimit-requests-reset' => $requests_reset->toISOString(),
            'anthropic-ratelimit-input-tokens-limit' => 80000,
            'anthropic-ratelimit-input-tokens-remaining' => 0,
            'anthropic-ratelimit-input-tokens-reset' => Carbon::now()->addSeconds(60)->toISOString(),
            'anthropic-ratelimit-output-tokens-limit' => 16000,
            'anthropic-ratelimit-output-tokens-remaining' => 15000,
            'anthropic-ratelimit-output-tokens-reset' => Carbon::now()->addSeconds(5)->toISOString(),
            'anthropic-ratelimit-tokens-limit' => 96000,
            'anthropic-ratelimit-tokens-remaining' => 15000,
            'anthropic-ratelimit-tokens-reset' => Carbon::now()->addSeconds(5)->toISOString(),
        ]
    );

    $response = Prism::text()
        ->using('anthropic', 'claude-3-5-sonnet-20240620')
        ->withPrompt('Who are you?')
        ->generate();

    expect($response->responseMeta->rateLimits)->toHaveCount(4);
    expect($response->responseMeta->rateLimits[0])->toBeInstanceOf(ProviderRateLimit::class);
    expect($response->responseMeta->rateLimits[0]->name)->toEqual('requests');
    expect($response->responseMeta->rateLimits[0]->limit)->toEqual(1000);
    expect($response->responseMeta->rateLimits[0]->remaining)->toEqual(500);
    expect($response->responseMeta->rateLimits[0]->resetsAt)->toEqual($requests_reset);
});

it('applies the citations request level providerMeta to all documents', function (): void {
    Prism::fake();

    $request = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            (new UserMessage(
                content: 'What color is the grass and sky?',
                additionalContent: [
                    Document::fromText('The grass is green. The sky is blue.'),
                ]
            )),
        ])
        ->withProviderMeta(Provider::Anthropic, ['citations' => true]);

    $payload = Text::buildHttpRequestPayload($request->toRequest());

    expect($payload['messages'])->toBe([[
        'role' => 'user',
        'content' => [
            [
                'type' => 'text',
                'text' => 'What color is the grass and sky?',
            ],
            [
                'type' => 'document',
                'source' => [
                    'type' => 'text',
                    'media_type' => 'text/plain',
                    'data' => 'The grass is green. The sky is blue.',
                ],
                'citations' => ['enabled' => true],
            ],
        ],
    ]]);
});

it('saves message parts with citations to additionalContent on response steps and assistant message for PDF documents', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-pdf-citations');

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            (new UserMessage(
                content: 'What color is the grass and sky?',
                additionalContent: [
                    Document::fromPath('tests/Fixtures/test-pdf.pdf'),
                ]
            )),
        ])
        ->withProviderMeta(Provider::Anthropic, ['citations' => true])
        ->generate();

    expect($response->text)->toEqual('According to the text, the grass is green and the sky is blue.');

    expect($response->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    /** @var MessagePartWithCitations */
    $messagePart = $response->additionalContent['messagePartsWithCitations'][1];

    expect($messagePart->text)->toBe('the grass is green');
    expect($messagePart->citations)->toHaveCount(1);
    expect($messagePart->citations[0]->type)->toBe('page_location');
    expect($messagePart->citations[0]->citedText)->toBe('The grass is green. ');
    expect($messagePart->citations[0]->startIndex)->toBe(1);
    expect($messagePart->citations[0]->endIndex)->toBe(2);
    expect($messagePart->citations[0]->documentIndex)->toBe(0);
    expect($messagePart->citations[0]->documentTitle)->toBe('All aboout the grass and the sky');

    expect($response->steps[0]->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    expect($response->messages->last()->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);
});

it('saves message parts with citations to additionalContent on response steps and assistant message for text documents', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-text-document-citations');

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            (new UserMessage(
                content: 'What color is the grass and sky?',
                additionalContent: [
                    Document::fromText('The grass is green. The sky is blue.'),
                ]
            )),
        ])
        ->withProviderMeta(Provider::Anthropic, ['citations' => true])
        ->generate();

    expect($response->text)->toBe("According to the documents:\nThe grass is green and the sky is blue.");

    expect($response->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    /** @var MessagePartWithCitations */
    $messagePart = $response->additionalContent['messagePartsWithCitations'][1];

    expect($messagePart->text)->toBe('The grass is green');
    expect($messagePart->citations)->toHaveCount(1);
    expect($messagePart->citations[0]->type)->toBe('char_location');
    expect($messagePart->citations[0]->citedText)->toBe('The grass is green. ');
    expect($messagePart->citations[0]->startIndex)->toBe(0);
    expect($messagePart->citations[0]->endIndex)->toBe(20);
    expect($messagePart->citations[0]->documentIndex)->toBe(0);
    expect($messagePart->citations[0]->documentTitle)->toBe('All aboout the grass and the sky');

    expect($response->steps[0]->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    expect($response->messages->last()->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);
});

it('saves message parts with citations to additionalContent on response steps and assistant message for custom content documents', function (): void {
    FixtureResponse::fakeResponseSequence('v1/messages', 'anthropic/generate-text-with-custom-content-document-citations');

    $response = Prism::text()
        ->using(Provider::Anthropic, 'claude-3-5-sonnet-latest')
        ->withMessages([
            (new UserMessage(
                content: 'What color is the grass and sky?',
                additionalContent: [
                    Document::fromChunks(['The grass is green.', 'The sky is blue.']),
                ]
            )),
        ])
        ->withProviderMeta(Provider::Anthropic, ['citations' => true])
        ->generate();

    expect($response->text)->toBe('According to the documents, the grass is green and the sky is blue.');

    expect($response->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    /** @var MessagePartWithCitations */
    $messagePart = $response->additionalContent['messagePartsWithCitations'][1];

    expect($messagePart->text)->toBe('the grass is green');
    expect($messagePart->citations)->toHaveCount(1);
    expect($messagePart->citations[0]->type)->toBe('content_block_location');
    expect($messagePart->citations[0]->citedText)->toBe('The grass is green.');
    expect($messagePart->citations[0]->startIndex)->toBe(0);
    expect($messagePart->citations[0]->endIndex)->toBe(1);

    expect($response->steps[0]->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);

    expect($response->messages->last()->additionalContent['messagePartsWithCitations'])->toHaveCount(5);
    expect($response->steps[0]->additionalContent['messagePartsWithCitations'][0])->toBeInstanceOf(MessagePartWithCitations::class);
});
