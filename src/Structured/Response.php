<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

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
