<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Embeddings;

use PrismPHP\Prism\ValueObjects\Embedding;
use PrismPHP\Prism\ValueObjects\EmbeddingsUsage;

readonly class Response
{
    /**
     * @param  Embedding[]  $embeddings
     */
    public function __construct(
        public array $embeddings,
        public EmbeddingsUsage $usage,
    ) {}
}
