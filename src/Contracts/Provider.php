<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Contracts;

use Generator;
use PrismPHP\Prism\Embeddings\Request as EmbeddingsRequest;
use PrismPHP\Prism\Embeddings\Response as EmbeddingsResponse;
use PrismPHP\Prism\Stream\Chunk;
use PrismPHP\Prism\Stream\Request as StreamRequest;
use PrismPHP\Prism\Structured\Request as StructuredRequest;
use PrismPHP\Prism\Structured\Response as StructuredResponse;
use PrismPHP\Prism\Text\Request as TextRequest;
use PrismPHP\Prism\Text\Response as TextResponse;

interface Provider
{
    public function text(TextRequest $request): TextResponse;

    public function structured(StructuredRequest $request): StructuredResponse;

    public function embeddings(EmbeddingsRequest $request): EmbeddingsResponse;

    /**
     * @return Generator<Chunk>
     */
    public function stream(StreamRequest $request): Generator;
}
