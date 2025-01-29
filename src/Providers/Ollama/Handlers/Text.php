<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Providers\Ollama\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Text
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

        ray('response', (string) $response->getBody());

        ray('data', $data);

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'Ollama Error: [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        return new ProviderResponse(
            text: data_get($data, 'message.content') ?? '',
            toolCalls: $this->mapToolCalls(data_get($data, 'message.tool_calls', []) ?? []),
            usage: new Usage(
                data_get($data, 'prompt_eval_count', 0),
                data_get($data, 'eval_count', 0),
            ),
            finishReason: $this->mapFinishReason($data),
            responseMeta: new ResponseMeta(
                id: '',
                model: $request->model,
            )
        );
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post('api/chat', ['model' => $request->model, 'system' => $request->systemPrompt, 'messages' => (new MessageMap($request->messages))->map(), 'tools' => ToolMap::map($request->tools), 'stream' => false, 'options' => array_filter([
            'temperature' => $request->temperature,
            'num_predict' => $request->maxTokens ?? 2048,
            'top_p' => $request->topP,
        ])]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $toolCalls
     * @return array<int, ToolCall>
     */
    protected function mapToolCalls(array $toolCalls): array
    {
        return array_map(fn (array $toolCall): ToolCall => new ToolCall(
            id: data_get($toolCall, 'id', ''),
            name: data_get($toolCall, 'function.name'),
            arguments: data_get($toolCall, 'function.arguments'),
        ), $toolCalls);
    }

    protected function mapFinishReason(array $data): FinishReason
    {
        if (! empty(data_get($data, 'message.tool_calls'))) {
            return FinishReason::ToolCalls;
        }

        return FinishReasonMap::map(data_get($data, 'done_reason', ''));
    }
}
