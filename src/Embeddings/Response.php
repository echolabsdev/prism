<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Embeddings;

use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;

readonly class Response
{
    /**
     * @param  array<int, int|string>  $embeddings
     */
    public function __construct(
        public array $embeddings,
        public EmbeddingsUsage $usage,
    ) {}
}
