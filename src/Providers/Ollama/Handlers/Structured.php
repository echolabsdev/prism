<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Providers\Ollama\Maps\ToolMap;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

class Structured
{
    public $maxSteps;

    use CallsTools;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): StructuredResponse
    {
        if ($this->responseBuilder->steps->count() >= $request->maxSteps) {
            throw new PrismException('Max steps exceeded');
        }

        $response = $this->sendRequest($request);

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls
        );

        $request = $request->addMessage($responseMessage);
        $this->responseBuilder->addResponseMessage($responseMessage);

        if ($response->finishReason === FinishReason::ToolCalls) {
            return $this->handleToolCalls($request, $response);
        }

        if ($response->finishReason === FinishReason::Stop) {
            return $this->handleStop($request);
        }
    }
    public function sendRequest(Request $request): ProviderResponse
    {
        try {
            $response = $this->client->post(
                'chat/completions',
                array_merge([
                    'model' => $request->model,
                    'messages' => (new MessageMap(
                        $request->messages,
                        $request->systemPrompt ?? ''
                    ))(),
                    'max_tokens' => $request->maxTokens ?? 2048,
                ], array_filter([
                    'temperature' => $request->temperature,
                    'top_p' => $request->topP,
                    'tools' => ToolMap::map($request->tools),
                ]))
            );

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
            toolCalls: $this->mapToolCalls(data_get($data, 'choices.0.message.tool_calls', []) ?? []),
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

    protected function handleToolCalls(Request $request, ProviderResponse $response): \EchoLabs\Prism\Structured\Response
    {
        $toolResults = $this->callTools(
            $request->tools,
            $response->toolCalls
        );

        $resultMessage = new ToolResultMessage($toolResults);

        $request = $request->addMessage($resultMessage);
        $this->responseBuilder->addResponseMessage($resultMessage);

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults ?? [],
            usage: $response->usage,
            response: $response->response,
            messages: $request->messages,
        ));

        return $this->handle($request);
    }

    protected function handleStop(Request $request): StructuredResponse
    {
        if ($this->responseBuilder->steps->count() >= $request->maxSteps) {
            throw new PrismException('Max steps exceeded');
        }

        $request = $this->appendMessageForJsonMode($request);

        $response = $this->sendRequest($request);

        $responseMessage = new AssistantMessage(
            $response->text,
            $response->toolCalls
        );

        $request = $request->addMessage($responseMessage);
        $this->responseBuilder->addResponseMessage($responseMessage);

        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: [],
            usage: $response->usage,
            response: $response->response,
            messages: $request->messages,
        ));

        return $this->responseBuilder->toResponse();
    }

    protected function shouldContinue(Request $request, ProviderResponse $response): bool
    {
        return $this->responseBuilder->steps->count() < $request->maxSteps
            && $response->finishReason !== FinishReason::Stop;
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

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new SystemMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
