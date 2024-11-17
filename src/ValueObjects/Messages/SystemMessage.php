<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\Support\Metadata;

class SystemMessage implements Message
{
    public function __construct(
        public readonly string $content,
        public readonly Metadata $metadata = new Metadata
    ) {}
}
