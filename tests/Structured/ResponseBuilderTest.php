<?php

declare(strict_types=1);

use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\Exceptions\PrismStructuredDecodingException;
use PrismPHP\Prism\Structured\ResponseBuilder;
use PrismPHP\Prism\Structured\Step;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\Usage;

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
