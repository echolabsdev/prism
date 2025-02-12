<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Embeddings\Request as EmbeddingsRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;

interface Provider
{
    public function text(TextRequest $request, int $currentStep): ProviderResponse;

    public function structured(StructuredRequest $request): ProviderResponse;

    public function embeddings(EmbeddingsRequest $request): EmbeddingsResponse;
}
