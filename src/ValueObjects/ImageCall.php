<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects;

class ImageCall
{
    public function __construct(
        public readonly string $url,
        public readonly ?string $fileType = null,
    ) {
    }
}
