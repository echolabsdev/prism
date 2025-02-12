<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request as StructuredRequest;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;

class Structured extends AnthropicHandlerAbstract
{
    /**
     * @var StructuredRequest
     */
    protected PrismRequest $request; // Redeclared for type hinting

    public function __construct(protected PendingRequest $client) {}

    /**
     * @param  StructuredRequest  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public static function buildHttpRequestPayload(PrismRequest $request): array
    {
        if (! $request->is(StructuredRequest::class)) {
            throw new \InvalidArgumentException('Request must be an instance of '.StructuredRequest::class);
        }

        return array_merge([
            'model' => $request->model,
            'messages' => MessageMap::map($request->messages, $request->providerMeta(Provider::Anthropic)),
            'max_tokens' => $request->maxTokens ?? 2048,
        ], array_filter([
            'system' => MessageMap::mapSystemMessages($request->messages, $request->systemPrompt),
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
        ]));
    }

    /**
     * @return StructuredRequest
     */
    #[\Override]
    protected function prepareRequest(): PrismRequest
    {
        return $this->appendMessageForJsonMode();
    }

    #[\Override]
    protected function buildProviderResponse(): ProviderResponse
    {
        $data = $this->httpResponse->json();

        return new ProviderResponse(
            text: $this->extractText($data),
            toolCalls: [],
            usage: new Usage(
                promptTokens: data_get($data, 'usage.input_tokens'),
                completionTokens: data_get($data, 'usage.output_tokens'),
                cacheWriteInputTokens: data_get($data, 'usage.cache_creation_input_tokens', null),
                cacheReadInputTokens: data_get($data, 'usage.cache_read_input_tokens', null)
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'stop_reason', '')),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
                rateLimits: $this->processRateLimits()
            ),
            additionalContent: array_filter([
                'messagePartsWithCitations' => $this->extractCitations($data),
            ])
        );
    }

    /**
     * @return StructuredRequest
     */
    protected function appendMessageForJsonMode(): PrismRequest
    {
        return $this->request->addMessage(new UserMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s %s",
            json_encode($this->request->schema->toArray(), JSON_PRETTY_PRINT),
            ($this->request->providerMeta(Provider::Anthropic)['citations'] ?? false)
                ? "\n\n Return the JSON as a single text block with a single set of citations."
                : ''
        )));
    }
}
