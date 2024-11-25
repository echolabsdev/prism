<?php

declare(strict_types=1);

namespace Tests\Testing;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Schema\ObjectSchema;
use EchoLabs\Prism\Schema\StringSchema;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\Usage;
use Exception;

it('fake responses using the prism fake for text', function (): void {
    $fake = Prism::fake([
        new ProviderResponse(
            text: 'The meaning of life is 42',
            toolCalls: [],
            usage: new Usage(42, 42),
            finishReason: FinishReason::Stop,
            response: ['id' => 'cpl_1234', 'model' => 'claude-3-sonnet'],
        ),
    ]);

    Prism::text()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('What is the meaning of life?')
        ->generate();

    $fake->assertCallCount(1);
    $fake->assertPrompt('What is the meaning of life?');
    $fake->assertRequest(function (array $requests): void {
        expect($requests)->toHaveCount(1);
        expect($requests[0])->toBeInstanceOf(TextRequest::class);
    });
});

it('fake responses using the prism fake for structured', function (): void {
    $fake = Prism::fake([
        new ProviderResponse(
            text: json_encode(['foo' => 'bar']),
            toolCalls: [],
            usage: new Usage(42, 42),
            finishReason: FinishReason::Stop,
            response: ['id' => 'cpl_1234', 'model' => 'claude-3-sonnet'],
        ),
    ]);

    Prism::structured()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('What is the meaning of life?')
        ->withSchema(new ObjectSchema(
            'foo',
            'foo schema',
            [
                new StringSchema('foo', 'foo value'),
            ]
        ))
        ->generate();

    $fake->assertCallCount(1);
    $fake->assertPrompt('What is the meaning of life?');
    $fake->assertRequest(function (array $requests): void {
        expect($requests)->toHaveCount(1);
        expect($requests[0])->toBeInstanceOf(StructuredRequest::class);
    });
});

it("throws an exception when it can't runs out of responses", function (): void {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Could not find a response for the request');

    Prism::fake([
        new ProviderResponse(
            text: 'The meaning of life is 42',
            toolCalls: [],
            usage: new Usage(42, 42),
            finishReason: FinishReason::Stop,
            response: ['id' => 'cpl_1234', 'model' => 'claude-3-sonnet'],
        ),
    ]);

    Prism::text()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('What is the meaning of life?')
        ->generate();

    Prism::text()
        ->using('anthropic', 'claude-3-sonnet')
        ->withPrompt('What is the meaning of life?')
        ->generate();
});
