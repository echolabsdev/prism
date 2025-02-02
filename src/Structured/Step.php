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
     */
    public function __construct(
        public string $text,
        public ?array $object,
        public FinishReason $finishReason,
        public Usage $usage,
        public ResponseMeta $responseMeta,
        public array $messages,
    ) {}
}
