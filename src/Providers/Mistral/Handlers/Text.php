<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Mistral\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Mistral\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Mistral\Maps\MessageMap;
use EchoLabs\Prism\Providers\Mistral\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Mistral\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\Text\Response as TextResponse;
use EchoLabs\Prism\Text\ResponseBuilder;
use EchoLabs\Prism\Text\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Text
{
    use CallsTools;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): TextResponse
    {
        $currentRequest = $request;
        $stepCount = 0;

        do {
            $stepCount++;
            try {
                $response = $this->sendRequest($currentRequest);
            } catch (Throwable $e) {
                throw PrismException::providerRequestError($request->model, $e);
            }

            $data = $response->json();

            if (! $data || data_get($data, 'object') === 'error') {
                throw PrismException::providerResponseError(vsprintf(
                    'Mistral Error:  [%s] %s',
                    [
                        data_get($data, 'type', 'unknown'),
                        data_get($data, 'message', 'unknown'),
                    ]
                ));
            }

            $text = data_get($data, 'choices.0.message.content') ?? '';
            $toolCalls = $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', []) ?? []);
            $finishReason = FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', ''));
            $toolResults = [];

            $responseMessage = new AssistantMessage($text, $toolCalls, []);
            $this->responseBuilder->addResponseMessage($responseMessage);
            $currentRequest = $currentRequest->addMessage($responseMessage);

            if ($finishReason === FinishReason::ToolCalls) {
                $toolResults = $this->callTools($currentRequest->tools, $toolCalls);
                $toolResultMessage = new ToolResultMessage($toolResults);
                $currentRequest = $currentRequest->addMessage($toolResultMessage);
            }

            $this->responseBuilder->addStep(new Step(
                text: $text,
                finishReason: $finishReason,
                toolCalls: $toolCalls,
                toolResults: $toolResults,
                usage: new Usage(
                    data_get($data, 'usage.prompt_tokens'),
                    data_get($data, 'usage.completion_tokens'),
                ),
                responseMeta: new ResponseMeta(
                    id: data_get($data, 'id'),
                    model: data_get($data, 'model'),
                ),
                messages: $currentRequest->messages,
                additionalContent: []
            ));

        } while ($stepCount < $request->maxSteps && $finishReason === FinishReason::ToolCalls);

        return $this->responseBuilder->toResponse();
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
                'tool_choice' => ToolChoiceMap::map($request->toolChoice),
            ]))
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
}
