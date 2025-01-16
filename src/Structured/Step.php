<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\Usage;

class Step
{
    /**
     * @param  array<mixed>  $object
     * @param  array{id: string, model: string}  $response
     * @param  Message[]  $messages
     */
    public function __construct(
        public readonly string $text,
        public readonly ?array $object,
        public readonly FinishReason $finishReason,
        public readonly Usage $usage,
        public readonly array $response,
        public readonly array $messages,
    ) {}
}
