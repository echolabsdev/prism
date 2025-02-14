<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\DeepSeek;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Embeddings\Request as EmbeddingsRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\DeepSeek\Handlers\Structured;
use EchoLabs\Prism\Providers\DeepSeek\Handlers\Text;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

readonly class DeepSeek implements Provider
{
    public function __construct(
        #[\SensitiveParameter] public string $apiKey,
    ) {}

    #[\Override]
    public function text(TextRequest $request, int $currentStep): ProviderResponse
    {
        $handler = new Text($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function structured(StructuredRequest $request): ProviderResponse
    {
        $handler = new Structured($this->client(
            $request->clientOptions,
            $request->clientRetry
        ));

        return $handler->handle($request);
    }

    #[\Override]
    public function embeddings(EmbeddingsRequest $request): EmbeddingsResponse
    {
        throw PrismException::unsupportedProviderAction(__FUNCTION__, class_basename($this));
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool}  $retry
     */
    protected function client(array $options, array $retry): PendingRequest
    {
        return Http::withHeaders(array_filter([
            'Authorization' => $this->apiKey !== '' && $this->apiKey !== '0' ? sprintf('Bearer %s', $this->apiKey) : null,
        ]))
            ->withOptions($options)
            ->retry(...$retry)
            ->baseUrl('https://api.deepseek.com/v1');
    }
}
