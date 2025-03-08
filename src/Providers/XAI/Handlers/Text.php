<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\XAI\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\Concerns\CallsTools;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\XAI\Concerns\MapsFinishReason;
use PrismPHP\Prism\Providers\XAI\Concerns\ValidatesResponses;
use PrismPHP\Prism\Providers\XAI\Maps\MessageMap;
use PrismPHP\Prism\Providers\XAI\Maps\ToolChoiceMap;
use PrismPHP\Prism\Providers\XAI\Maps\ToolMap;
use PrismPHP\Prism\Text\Request;
use PrismPHP\Prism\Text\Response as TextResponse;
use PrismPHP\Prism\Text\ResponseBuilder;
use PrismPHP\Prism\Text\Step;
use PrismPHP\Prism\ValueObjects\Messages\AssistantMessage;
use PrismPHP\Prism\ValueObjects\Messages\ToolResultMessage;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\ToolCall;
use PrismPHP\Prism\ValueObjects\ToolResult;
use PrismPHP\Prism\ValueObjects\Usage;
use Throwable;

class Text
{
    use CallsTools;
    use MapsFinishReason;
    use ValidatesResponses;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): TextResponse
    {
        $data = $this->sendRequest($request);

        $this->validateResponse($data);

        $responseMessage = new AssistantMessage(
            data_get($data, 'choices.0.message.content') ?? '',
            $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', [])),
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        return match ($this->mapFinishReason($data)) {
            FinishReason::ToolCalls => $this->handleToolCalls($data, $request),
            FinishReason::Stop => $this->handleStop($data, $request),
            default => throw new PrismException('XAI: unknown finish reason'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleToolCalls(array $data, Request $request): TextResponse
    {
        $toolResults = $this->callTools(
            $request->tools(),
            $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', [])),
        );

        $request->addMessage(new ToolResultMessage($toolResults));

        $this->addStep($data, $request, $toolResults);

        if ($this->shouldContinue($request)) {
            return $this->handle($request);
        }

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleStop(array $data, Request $request): TextResponse
    {
        $this->addStep($data, $request);

        return $this->responseBuilder->toResponse();
    }

    protected function shouldContinue(Request $request): bool
    {
        return $this->responseBuilder->steps->count() < $request->maxSteps();
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendRequest(Request $request): array
    {
        try {
            $response = $this->client->post(
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

            return $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
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
     * @param  array<string, mixed>  $data
     * @param  array<int, ToolResult>  $toolResults
     */
    protected function addStep(array $data, Request $request, array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: data_get($data, 'choices.0.message.content') ?? '',
            finishReason: $this->mapFinishReason($data),
            toolCalls: $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', [])),
            toolResults: $toolResults,
            usage: new Usage(
                data_get($data, 'usage.prompt_tokens'),
                data_get($data, 'usage.completion_tokens'),
            ),
            meta: new Meta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
            ),
            messages: $request->messages(),
            additionalContent: [],
            systemPrompts: $request->systemPrompts(),
        ));
    }
}
