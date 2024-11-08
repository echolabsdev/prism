<?php

declare(strict_types=1);

namespace Tests\Testing;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\Usage;
use Exception;

it('fake responses using the prism fake', function (): void {
    $fake = Prism::fake([
        new ProviderResponse(
            'The meaning of life is 42',
            [],
            new Usage(42, 42),
            FinishReason::Stop,
            ['id' => 'cpl_1234', 'model' => 'claude-3-sonnet'],
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

it("throws an exception when it can't runs out of responses", function (): void {
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Could not find a response for the request');

    Prism::fake([
        new ProviderResponse(
            'The meaning of life is 42',
            [],
            new Usage(42, 42),
            FinishReason::Stop,
            ['id' => 'cpl_1234', 'model' => 'claude-3-sonnet'],
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
