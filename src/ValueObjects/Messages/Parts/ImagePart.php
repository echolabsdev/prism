<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Parts;

class ImagePart
{
    public function __construct(
        public readonly string $image,
        public readonly ?string $mimeType = null,
    ) {}
}
