<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Structured;

use Illuminate\Support\Collection;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\Usage;

readonly class Response
{
    /**
     * @param  Collection<int, Step>  $steps
     * @param  Collection<int, Message>  $responseMessages
     * @param  array<mixed>  $structured
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly Collection $steps,
        public readonly Collection $responseMessages,
        public readonly string $text,
        public readonly ?array $structured,
        public readonly FinishReason $finishReason,
        public readonly Usage $usage,
        public readonly Meta $meta,
        public readonly array $additionalContent = []
    ) {}
}
