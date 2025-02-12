<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\OpenAI\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolCallMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolMap;
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

    public function handle(Request $request, int $currentStep): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request, $currentStep);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();

        if (! $data || data_get($data, 'error')) {
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

    public function sendRequest(Request $request, int $currentStep): Response
    {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model,
                'messages' => (new MessageMap($request->messages, $request->systemPrompt ?? ''))(),
                'max_completion_tokens' => $request->maxTokens,
            ], array_filter([
                'temperature' => $request->temperature,
                'top_p' => $request->topP,
                'tools' => ToolMap::map($request->tools),
                'tool_choice' => ToolChoiceMap::map($request->toolChoice, $currentStep),
            ]))
        );
    }
}
