<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismStructuredDecodingException;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\Usage;

test('throws a PrismStructuredDecodingException if the response is not valid json', function (): void {
    $builder = new ResponseBuilder;

    $builder->addStep(new Step(
        text: 'This is not valid json',
        systemPrompts: [],
        finishReason: FinishReason::Stop,
        usage: new Usage(
            promptTokens: 0,
            completionTokens: 0
        ),
        meta: new Meta(
            id: '123',
            model: 'Test',
        ),
        messages: [],
    ));

    $builder->toResponse();
})->throws(PrismStructuredDecodingException::class);
