<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Note: Prism currently only supports Documents with Anthropic.
 */
readonly class Document
{
    public string $dataFormat;

    public function __construct(
        public string $document,
        public string $mimeType,
        ?string $dataFormat = null
    ) {
        // Done this way to avoid assigning a readonly property twice.
        $this->dataFormat = $dataFormat ?? (Str::startsWith($this->mimeType, 'text/') ? 'text' : 'base64');
    }

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

        $isText = Str::startsWith($mimeType, 'text/');

        return new self(
            document: $isText ? $content : base64_encode($content),
            mimeType: $mimeType,
            dataFormat: $isText ? 'text' : 'base64'
        );
    }

    public static function fromBase64(string $document, string $mimeType): self
    {
        return new self(
            document: $document,
            mimeType: $mimeType,
            dataFormat: 'base64'
        );
    }

    public static function fromText(string $text): self
    {
        return new self(
            document: $text,
            mimeType: 'text/plain',
            dataFormat: 'text'
        );
    }
}
