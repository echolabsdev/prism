<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\ValueObjects;

class MessagePartWithCitations
{
    /**
     * @param  Citation[]  $citations
     */
    public function __construct(
        public readonly string $text,
        public readonly array $citations = []
    ) {}

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromContentBlock(array $data): self
    {
        return new self(
            $data['text'],
            array_map(function (array $citation): \EchoLabs\Prism\Providers\Anthropic\ValueObjects\Citation {
                $indexPropertyCommonPart = match ($citation['type']) {
                    'page_location' => 'page_number',
                    'char_location' => 'char_index',
                    'content_block_location' => 'block_index',
                    default => throw new \InvalidArgumentException("Unknown citation type: {$citation['type']}"),
                };

                return new Citation(
                    type: $citation['type'],
                    citedText: data_get($citation, 'cited_text'),
                    startIndex: data_get($citation, "start_$indexPropertyCommonPart"),
                    endIndex: data_get($citation, "end_$indexPropertyCommonPart"),
                    documentIndex: data_get($citation, 'document_index'),
                    documentTitle: data_get($citation, 'document_title')
                );
            }, $data['citations'] ?? [])
        );
    }
}
