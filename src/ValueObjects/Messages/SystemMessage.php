<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;

class SystemMessage implements Message
{
    public function __construct(
        public readonly string $content,
    ) {}
}
