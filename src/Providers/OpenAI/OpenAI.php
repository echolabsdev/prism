<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI;

use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Throwable;

class OpenAI implements Provider
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $url,
        public readonly ?string $organization,
    ) {}

    #[\Override]
    public function text(TextRequest $request): ProviderResponse
    {
        try {
            $response = $this
                ->client($request->clientOptions)
                ->messages(
                    model: $request->model,
                    messages: (new MessageMap(
                        $request->messages,
                        $request->systemPrompt ?? '',
                    ))(),
                    maxTokens: $request->maxTokens,
                    temperature: $request->temperature,
                    topP: $request->topP,
                    tools: Tool::map($request->tools),
                );
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();

        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'OpenAI Error:  [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        return new ProviderResponse(
            text: data_get($data, 'choices.0.message.content') ?? '',
            toolCalls: $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', [])),
            usage: new Usage(
                data_get($data, 'usage.prompt_tokens'),
                data_get($data, 'usage.completion_tokens'),
            ),
            finishReason: $this->mapFinishReason(data_get($data, 'choices.0.finish_reason', '')),
            response: [
                'id' => data_get($data, 'id'),
                'model' => data_get($data, 'model'),
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @return array<int, ToolCall>
     */
    protected function mapToolCalls(array $toolCalls): array
    {
        return array_map(fn (array $toolCall): ToolCall => new ToolCall(
            id: data_get($toolCall, 'id'),
            name: data_get($toolCall, 'function.name'),
            arguments: data_get($toolCall, 'function.arguments'),
        ), $toolCalls);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function client(array $options = []): Client
    {
        return new Client(
            apiKey: $this->apiKey,
            url: $this->url,
            organization: $this->organization,
            options: $options,
        );
    }

    protected function mapFinishReason(string $stopReason): FinishReason
    {
        return match ($stopReason) {
            'stop', => FinishReason::Stop,
            'tool_calls' => FinishReason::ToolCalls,
            'length' => FinishReason::Length,
            'content_filter' => FinishReason::ContentFilter,
            default => FinishReason::Unknown,
        };
    }
}
