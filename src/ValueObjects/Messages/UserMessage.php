<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\Parts\ImagePart;
use EchoLabs\Prism\ValueObjects\Messages\Parts\TextPart;

class UserMessage implements Message
{
    /**
     * @param  string|array<int, TextPart|ImagePart>  $content
     */
    public function __construct(
        public readonly string|array $content,
    ) {}
}
