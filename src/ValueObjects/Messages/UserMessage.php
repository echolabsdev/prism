<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\Parts\ImagePart;

class UserMessage implements Message
{
    /**
     * @param  ImagePart[]  $parts
     */
    public function __construct(
        public readonly string $content,
        public readonly array $parts = []
    ) {}

    public function imageParts(): array
    {
        return collect($this->parts)
            ->where(fn ($part): bool => $part instanceof ImagePart)
            ->toArray();
    }
}
