<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Image
{
    public function __construct(
        public readonly string $image,
        public readonly ?string $mimeType = null,
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

        return new self(
            base64_encode($content),
            File::mimeType($path) ?: null,
        );
    }

    public static function fromUrl(string $url): self
    {
        return new self($url);
    }

    public static function fromBase64(string $image, string $mimeType): self
    {
        return new self(
            $image,
            $mimeType
        );
    }

    public function isUrl(): bool
    {
        return Str::isUrl($this->image);
    }
}
