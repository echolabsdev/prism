<?php

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Providers\Ollama\Maps\ToolCallMap;
use EchoLabs\Prism\Providers\Ollama\Maps\ToolMap;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Structured
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request);
            $data = $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'Ollama Error:  [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        return new ProviderResponse(
            text: data_get($data, 'choices.0.message.content') ?? '',
            toolCalls: ToolCallMap::map(data_get($data, 'choices.0.message.tool_calls', [])),
            usage: new Usage(
                data_get($data, 'usage.prompt_tokens'),
                data_get($data, 'usage.completion_tokens'),
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', '')),
            response: [
                'id' => data_get($data, 'id'),
                'model' => data_get($data, 'model'),
            ]
        );
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model,
                'messages' => (new MessageMap($request->messages, $request->systemPrompt ?? ''))(),
                'max_tokens' => $request->maxTokens ?? 2048,
            ], array_filter([
                'temperature' => $request->temperature,
                'top_p' => $request->topP,
                'tools' => ToolMap::map($request->tools),
                'response_format' => $request->isFinalResponse ? $this->mapResponseFormat($request) : null,
            ]))
        );
    }

    /**
     * @return array{type: 'json_schema', json_schema: array<string, mixed>}
     */
    protected function mapResponseFormat(Request $request): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => array_filter([
                'name' => $request->schema->name(),
                'schema' => $request->schema->toArray(),
                'strict' => $request->providerMeta(Provider::OpenAI, 'schema.strict'),
            ]),
        ];
    }
}
