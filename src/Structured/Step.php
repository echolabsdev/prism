<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;

readonly class Step
{
    /**
     * @param  array<mixed>|null  $object
     * @param  Message[]  $messages
     * @param  array<string,mixed>  $additionalContent
     */
    public function __construct(
        public readonly string $text,
        public readonly ?array $object,
        public readonly FinishReason $finishReason,
        public readonly Usage $usage,
        public readonly ResponseMeta $responseMeta,
        public readonly array $messages,
        public readonly array $additionalContent = []
    ) {}
}
