<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects\Messages;

use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Contracts\Message;

class SystemMessage implements Message
{
    use HasProviderMeta;

    public function __construct(
        public readonly string $content
    ) {}
}
