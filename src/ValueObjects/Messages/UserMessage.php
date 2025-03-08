<?php

declare(strict_types=1);

namespace PrismPHP\Prism\ValueObjects\Messages;

use PrismPHP\Prism\Concerns\HasProviderMeta;
use PrismPHP\Prism\Contracts\Message;
use PrismPHP\Prism\ValueObjects\Messages\Support\Document;
use PrismPHP\Prism\ValueObjects\Messages\Support\Image;
use PrismPHP\Prism\ValueObjects\Messages\Support\Text;

class UserMessage implements Message
{
    use HasProviderMeta;

    /**
     * @param  array<int, Text|Image|Document>  $additionalContent
     */
    public function __construct(
        protected readonly string $content,
        protected array $additionalContent = []
    ) {
        $this->additionalContent[] = new Text($content);
    }

    public function text(): string
    {
        $result = '';

        foreach ($this->additionalContent as $content) {
            if ($content instanceof Text) {
                $result .= $content->text;
            }
        }

        return $result;
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

    /**
     * Note: Prism currently only supports Documents with Anthropic.
     *
     * @return Document[]
     */
    public function documents(): array
    {
        return collect($this->additionalContent)
            ->where(fn ($part): bool => $part instanceof Document)
            ->toArray();
    }
}
