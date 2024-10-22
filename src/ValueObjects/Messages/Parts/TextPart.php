<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Parts;

class TextPart
{
    public function __construct(public readonly string $text) {}
}
