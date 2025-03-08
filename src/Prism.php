<?php

declare(strict_types=1);

namespace PrismPHP\Prism;

use PrismPHP\Prism\Contracts\Provider;
use PrismPHP\Prism\Embeddings\PendingRequest as PendingEmbeddingRequest;
use PrismPHP\Prism\Embeddings\Response as EmbeddingResponse;
use PrismPHP\Prism\Enums\Provider as ProviderEnum;
use PrismPHP\Prism\Stream\PendingRequest as PendingStreamRequest;
use PrismPHP\Prism\Structured\PendingRequest as PendingStructuredRequest;
use PrismPHP\Prism\Structured\Response as StructuredResponse;
use PrismPHP\Prism\Testing\PrismFake;
use PrismPHP\Prism\Text\PendingRequest as PendingTextRequest;
use PrismPHP\Prism\Text\Response as TextResponse;

class Prism
{
    /**
     * @param  array<int, TextResponse|StructuredResponse|EmbeddingResponse>  $responses
     */
    public static function fake(array $responses = []): PrismFake
    {
        $fake = new PrismFake($responses);

        app()->instance(PrismManager::class, new class($fake) extends PrismManager
        {
            public function __construct(
                private readonly PrismFake $fake
            ) {}

            public function resolve(ProviderEnum|string $name, array $providerConfig = []): Provider
            {
                $this->fake->setProviderConfig($providerConfig);

                return $this->fake;
            }
        });

        return $fake;
    }

    public static function text(): PendingTextRequest
    {
        return new PendingTextRequest;
    }

    public static function stream(): PendingStreamRequest
    {
        return new PendingStreamRequest;
    }

    public static function structured(): PendingStructuredRequest
    {
        return new PendingStructuredRequest;
    }

    public static function embeddings(): PendingEmbeddingRequest
    {
        return new PendingEmbeddingRequest;
    }
}
