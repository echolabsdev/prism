<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Support;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Note: Prism currently only supports Documents with Anthropic.
 */
class Document
{
    public function __construct(
        public readonly string $document,
        public readonly string $mimeType
    ) {}

    public static function fromPath(string $path): self
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("{$path} is not a file");
        }

        $content = file_get_contents($path);

        if ($content === '' || $content === '0' || $content === false) {
            throw new InvalidArgumentException("{$path} is empty");
        }

        $mimeType = File::mimeType($path);

        if ($mimeType === false) {
            throw new InvalidArgumentException("Could not determine mime type for {$path}");
        }

        return new self(
            base64_encode($content),
            $mimeType,
        );
    }

    public static function fromBase64(string $document, string $mimeType): self
    {
        return new self(
            $document,
            $mimeType
        );
    }
}
