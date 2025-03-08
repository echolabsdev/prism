<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Gemini\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\Concerns\CallsTools;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\Gemini\Maps\FinishReasonMap;
use PrismPHP\Prism\Providers\Gemini\Maps\MessageMap;
use PrismPHP\Prism\Providers\Gemini\Maps\ToolCallMap;
use PrismPHP\Prism\Providers\Gemini\Maps\ToolChoiceMap;
use PrismPHP\Prism\Providers\Gemini\Maps\ToolMap;
use PrismPHP\Prism\Text\Request;
use PrismPHP\Prism\Text\Response as TextResponse;
use PrismPHP\Prism\Text\ResponseBuilder;
use PrismPHP\Prism\Text\Step;
use PrismPHP\Prism\ValueObjects\Messages\AssistantMessage;
use PrismPHP\Prism\ValueObjects\Messages\ToolResultMessage;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\ToolResult;
use PrismPHP\Prism\ValueObjects\Usage;
use Throwable;

class Text
{
    use CallsTools;

    protected ResponseBuilder $responseBuilder;

    public function __construct(
        protected PendingRequest $client,
        #[\SensitiveParameter] protected string $apiKey,
    ) {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): TextResponse
    {
        $data = $this->sendRequest($request);

        $this->validateResponse($data);

        $isToolCall = ! empty(data_get($data, 'candidates.0.content.parts.0.functionCall'));

        $responseMessage = new AssistantMessage(
            data_get($data, 'message.content') ?? '',
            $isToolCall ? ToolCallMap::map(data_get($data, 'candidates.0.content.parts', [])) : [],
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        $finishReason = FinishReasonMap::map(
            data_get($data, 'candidates.0.finishReason'),
            $isToolCall
        );

        return match ($finishReason) {
            FinishReason::ToolCalls => $this->handleToolCalls($data, $request),
            FinishReason::Stop, FinishReason::Length => $this->handleStop($data, $request, $finishReason),
            default => throw new PrismException('Gemini: unhandled finish reason'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendRequest(Request $request): array
    {
        try {
            $generationConfig = array_filter([
                'temperature' => $request->temperature(),
                'topP' => $request->topP(),
                'maxOutputTokens' => $request->maxTokens(),
            ]);

            $tools = ToolMap::map($request->tools());

            $response = $this->client->post(
                "{$request->model()}:generateContent",
                array_filter([
                    ...(new MessageMap($request->messages(), $request->systemPrompts()))(),
                    'generationConfig' => $generationConfig !== [] ? $generationConfig : null,
                    'tools' => $tools !== [] ? ['function_declarations' => $tools] : null,
                    'tool_config' => $request->toolChoice() ? ToolChoiceMap::map($request->toolChoice()) : null,
                    'safetySettings' => $request->providerMeta(Provider::Gemini, 'safetySettings'),
                ])
            );

            return $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: [%s] %s',
                [
                    data_get($data, 'error.code', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleStop(array $data, Request $request, FinishReason $finishReason): TextResponse
    {
        $this->addStep($data, $request, $finishReason);

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleToolCalls(array $data, Request $request): TextResponse
    {
        $toolResults = $this->callTools(
            $request->tools(),
            ToolCallMap::map(data_get($data, 'candidates.0.content.parts', []))
        );

        $request->addMessage(new ToolResultMessage($toolResults));

        $this->addStep($data, $request, FinishReason::ToolCalls, $toolResults);

        if ($this->shouldContinue($request)) {
            return $this->handle($request);
        }

        return $this->responseBuilder->toResponse();
    }

    protected function shouldContinue(Request $request): bool
    {
        return $this->responseBuilder->steps->count() < $request->maxSteps();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  ToolResult[]  $toolResults
     */
    protected function addStep(array $data, Request $request, FinishReason $finishReason, array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
            finishReason: $finishReason,
            toolCalls: $finishReason === FinishReason::ToolCalls ? ToolCallMap::map(data_get($data, 'candidates.0.content.parts', [])) : [],
            toolResults: $toolResults,
            usage: new Usage(
                data_get($data, 'usageMetadata.promptTokenCount', 0),
                data_get($data, 'usageMetadata.candidatesTokenCount', 0)
            ),
            meta: new Meta(
                id: data_get($data, 'id', ''),
                model: data_get($data, 'modelVersion'),
            ),
            messages: $request->messages(),
            systemPrompts: $request->systemPrompts(),
            additionalContent: [],
        ));
    }
}
