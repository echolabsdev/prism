<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
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
            )
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
                'system' => MessageMap::mapSystemMessages($request->messages, $request->systemPrompt),
                'temperature' => $request->temperature,
                'top_p' => $request->topP,
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

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new UserMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
