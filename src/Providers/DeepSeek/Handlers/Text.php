<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\DeepSeek\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\DeepSeek\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\DeepSeek\Maps\MessageMap;
use EchoLabs\Prism\Providers\DeepSeek\Maps\ToolCallMap;
use EchoLabs\Prism\Providers\DeepSeek\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\DeepSeek\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
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
            throw PrismException::providerRequestError($request->model(), $e);
        }

        if (! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'DeepSeek Error: %s',
                [
                    (string) $response->getBody(),
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
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
            )
        );
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model(),
                'messages' => (new MessageMap($request->messages(), $request->systemPrompt() ?? ''))(),
                'max_completion_tokens' => $request->maxTokens(),
            ], array_filter([
                'temperature' => $request->temperature(),
                'top_p' => $request->topP(),
                'tools' => ToolMap::map($request->tools()),
                'tool_choice' => ToolChoiceMap::map($request->toolChoice()),
            ]))
        );
    }
}
