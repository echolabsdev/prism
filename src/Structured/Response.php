<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Structured;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

readonly class Response
{
    /**
     * @param  Collection<int, Step>  $steps
     * @param  Collection<int, Message>  $responseMessages
     * @param  array<mixed>  $structured
     */
    public function __construct(
        public Collection $steps,
        public Collection $responseMessages,
        public string $text,
        public ?array $structured,
        public FinishReason $finishReason,
        public Usage $usage,
        public ResponseMeta $responseMeta,
    ) {}
}
