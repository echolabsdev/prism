<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;

/**
 * @template TRequest of TextRequest
 */
class Text extends AnthropicHandlerAbstract
{
    /**
     * @param  TextRequest  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public static function buildHttpRequestPayload(PrismRequest $request): array
    {
        if (! $request instanceof TextRequest) {
            throw new \InvalidArgumentException('Request must be an instance of '.TextRequest::class);
        }

        return array_merge([
            'model' => $request->model,
            'messages' => MessageMap::map($request->messages),
            'max_tokens' => $request->maxTokens ?? 2048,
        ], array_filter([
            'system' => MessageMap::mapSystemMessages($request->messages, $request->systemPrompt),
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'tools' => ToolMap::map($request->tools),
            'tool_choice' => ToolChoiceMap::map($request->toolChoice),
        ]));
    }

    #[\Override]
    protected function prepareRequest(): PrismRequest
    {
        return $this->request;
    }

    #[\Override]
    protected function buildProviderResponse(): ProviderResponse
    {
        $data = $this->httpResponse->json();

        return new ProviderResponse(
            text: $this->extractText($data),
            toolCalls: $this->extractToolCalls($data),
            usage: new Usage(
                promptTokens: data_get($data, 'usage.input_tokens'),
                completionTokens: data_get($data, 'usage.output_tokens'),
                cacheWriteInputTokens: data_get($data, 'usage.cache_creation_input_tokens'),
                cacheReadInputTokens: data_get($data, 'usage.cache_read_input_tokens')
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'stop_reason', '')),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
                rateLimits: $this->processRateLimits()
            )
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return ToolCall[]
     */
    protected function extractToolCalls(array $data): array
    {
        $toolCalls = array_map(function ($content) {
            if (data_get($content, 'type') === 'tool_use') {
                return new ToolCall(
                    id: data_get($content, 'id'),
                    name: data_get($content, 'name'),
                    arguments: data_get($content, 'input')
                );
            }
        }, data_get($data, 'content', []));

        return array_values(array_filter($toolCalls));
    }
}
