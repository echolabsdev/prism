<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\XAI\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\XAI\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\XAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\XAI\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\XAI\Maps\ToolMap;
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

class Text
{
    use CallsTools;

    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): TextResponse
    {
        $responseBuilder = new ResponseBuilder;
        $currentRequest = $request;
        $stepCount = 0;

        do {
            $stepCount++;
            $response = $this->sendRequest($currentRequest);
            $data = $response->json();

            if (! $data || data_get($data, 'error')) {
                throw PrismException::providerResponseError(vsprintf(
                    'xAI Error:  [%s] %s',
                    [
                        data_get($data, 'error.type', 'unknown'),
                        data_get($data, 'error.message', 'unknown'),
                    ]
                ));
            }

            $text = data_get($data, 'choices.0.message.content') ?? '';
            $finishReason = FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', ''));
            $toolCalls = $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', []));
            $toolResults = [];

            $responseMessage = new AssistantMessage($text, $toolCalls, []);
            $responseBuilder->addResponseMessage($responseMessage);
            $currentRequest = $currentRequest->addMessage($responseMessage);

            if ($finishReason === FinishReason::ToolCalls) {
                $toolResults = $this->callTools($currentRequest->tools, $toolCalls);
                $toolResultMessage = new ToolResultMessage($toolResults);
                $currentRequest = $currentRequest->addMessage($toolResultMessage);
            }

            $step = new Step(
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
                additionalContent: [],
            );

            $responseBuilder->addStep($step);

        } while ($stepCount < $request->maxSteps && $finishReason === FinishReason::ToolCalls);

        return $responseBuilder->toResponse();
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model(),
                'messages' => (new MessageMap($request->messages(), $request->systemPrompts()))(),
                'max_tokens' => $request->maxTokens() ?? 2048,
            ], array_filter([
                'temperature' => $request->temperature(),
                'top_p' => $request->topP(),
                'tools' => ToolMap::map($request->tools()),
                'tool_choice' => ToolChoiceMap::map($request->toolChoice()),
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
