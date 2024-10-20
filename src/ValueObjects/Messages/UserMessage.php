<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\ImageCall;

class UserMessage implements Message
{
    /**
     * @param  ImageCall[]  $images
     */
    public function __construct(
        protected readonly string $content,
        public readonly array $images = [],
    ) {
    }

    #[\Override]
    public function content(): string
    {
        return $this->content;
    }

    public function hasImages(): bool
    {
        return count($this->images) > 0;
    }
}
