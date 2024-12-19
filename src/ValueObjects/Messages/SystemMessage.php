<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Message;

class SystemMessage implements Message
{
    use HasProviderMeta;

    public function __construct(
        public readonly string $content
    ) {}
}
