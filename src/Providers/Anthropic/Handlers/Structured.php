<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
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
            $request = $this->appendMessageForJsonMode($request);
            $response = $this->sendRequest($request);
            $data = $response->json();

        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        if (data_get($data, 'type') === 'error') {
            throw PrismException::providerResponseError(vsprintf(
                'Anthropic Error: [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message'),
                ]
            ));
        }

        return new ProviderResponse(
            text: $this->extractText($data),
            toolCalls: $this->extractToolCalls($data),
            usage: new Usage(
                data_get($data, 'usage.input_tokens'),
                data_get($data, 'usage.output_tokens'),
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'stop_reason', '')),
            response: [
                'id' => data_get($data, 'id'),
                'model' => data_get($data, 'model'),
            ]
        );
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'messages',
            array_merge([
                'model' => $request->model,
                'messages' => MessageMap::map($request->messages),
                'max_tokens' => $request->maxTokens ?? 2048,
            ], array_filter([
                'system' => $request->systemPrompt,
                'temperature' => $request->temperature,
                'top_p' => $request->topP,
                'tools' => ToolMap::map($request->tools),
                'tool_choice' => ToolChoiceMap::map($request->toolChoice),
            ]))
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function extractText(array $data): string
    {
        return array_reduce(data_get($data, 'content', []), function (string $text, array $content): string {
            if (data_get($content, 'type') === 'text') {
                $text .= data_get($content, 'text');
            }

            return $text;
        }, '');
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

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new UserMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
