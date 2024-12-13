<?php

declare(strict_types=1);

namespace EchoLabs\Prism\ValueObjects\Messages;

use EchoLabs\Prism\Concerns\HasProviderMeta;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\Messages\Support\Image;
use EchoLabs\Prism\ValueObjects\Messages\Support\Text;

class UserMessage implements Message
{
    use HasProviderMeta;

    /**
     * @param  array<int, Text|Image>  $additionalContent
     */
    public function __construct(
        protected readonly string $content,
        protected array $additionalContent = []
    ) {
        $this->additionalContent[] = new Text($content);
    }

    public function text(): string
    {
        return collect($this->additionalContent)
            ->map(function ($content) {
                if ($content instanceof Text) {
                    return $content->text;
                }
            })
            ->implode('');
    }

    /**
     * @return Image[]
     */
    public function images(): array
    {
        return collect($this->additionalContent)
            ->where(fn ($part): bool => $part instanceof Image)
            ->toArray();
    }
}
