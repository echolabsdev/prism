<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

class Response
{
    /**
     * @param  Collection<int, Step>  $steps
     * @param  Collection<int, Message>  $responseMessages
     * @param  array<mixed>  $structured
     */
    public function __construct(
        public readonly Collection $steps,
        public readonly Collection $responseMessages,
        public readonly string $text,
        public readonly ?array $structured,
        public readonly FinishReason $finishReason,
        public readonly Usage $usage,
        public readonly ResponseMeta $responseMeta,
    ) {}
}
