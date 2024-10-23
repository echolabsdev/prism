<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Support;

class Text
{
    public function __construct(public readonly string $text) {}
}
