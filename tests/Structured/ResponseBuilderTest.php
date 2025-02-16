<?php

declare(strict_types=1);

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismStructuredDecodingException;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;

test('throws a PrismStructuredDecodingException if the response is not valid json', function (): void {
    $builder = new ResponseBuilder;

    $builder->addStep(new Step(
        text: 'This is not valid json',
        systemPrompts: [],
        object: null,
        finishReason: FinishReason::Stop,
        usage: new Usage(
            promptTokens: 0,
            completionTokens: 0
        ),
        responseMeta: new ResponseMeta(
            id: '123',
            model: 'Test',
        ),
        messages: [],
    ));

    $builder->toResponse();

})->throws(PrismStructuredDecodingException::class);
