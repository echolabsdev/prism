<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

class Structured
{
    use CallsTools;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): StructuredResponse
    {
        $this->ensureStepsNotExceeded($request);

        $response = $this->sendRequest($request);
        $request = $this->handleResponseMessage($request, $response);

        return match ($response->finishReason) {
            FinishReason::ToolCalls => $this->handleToolCalls($request, $response),
            FinishReason::Stop => $this->handleStop($request),
            default => throw PrismException::providerResponseError('Unexpected finish reason')
        };
    }

    protected function sendRequest(Request $request, bool $withSchema = false): ProviderResponse
    {
        try {
            $response = $this->client->post(
                'messages',
                $this->buildRequestPayload($request)
            );

            $data = $response->json();

            if (data_get($data, 'type') === 'error') {
                throw PrismException::providerResponseError(vsprintf(
                    'Anthropic Error: [%s] %s',
                    [
                        data_get($data, 'error.type', 'unknown'),
                        data_get($data, 'error.message'),
                    ]
                ));
            }

            return $this->buildProviderResponse($data);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }
    }

    protected function buildRequestPayload(Request $request): array
    {
        return array_merge([
            'model' => $request->model,
            'messages' => MessageMap::map($request->messages),
            'max_tokens' => $request->maxTokens ?? 2048,
        ], array_filter([
            'system' => $request->systemPrompt,
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'tools' => ToolMap::map($request->tools),
            'tool_choice' => ToolChoiceMap::map($request->toolChoice),
        ]));
    }

    protected function buildProviderResponse(array $data): ProviderResponse
    {
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

    protected function extractText(array $data): string
    {
        return array_reduce(data_get($data, 'content', []), function (string $text, array $content): string {
            if (data_get($content, 'type') === 'text') {
                $text .= data_get($content, 'text');
            }

            return $text;
        }, '');
    }

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

    protected function handleToolCalls(Request $request, ProviderResponse $response): StructuredResponse
    {
        $toolResults = $this->callTools(
            $request->tools,
            $response->toolCalls,
        );
        $request = $this->addToolResultMessage($request, $toolResults);
        $this->addResponseStep($request, $response, $toolResults);

        return $this->handle($request);
    }

    protected function addToolResultMessage(Request $request, array $toolResults): Request
    {
        $message = new ToolResultMessage($toolResults);
        $this->responseBuilder->addResponseMessage($message);

        return $request->addMessage($message);
    }

    protected function addResponseStep(Request $request, ProviderResponse $response, array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults,
            usage: $response->usage,
            response: $response->response,
            messages: $request->messages,
        ));
    }

    protected function ensureStepsNotExceeded(Request $request): void
    {
        if ($this->responseBuilder->steps->count() >= $request->maxSteps) {
            throw new PrismException('Max steps exceeded');
        }
    }

    protected function handleResponseMessage(Request $request, ProviderResponse $response): Request
    {
        $message = new AssistantMessage($response->text, $response->toolCalls);
        $this->responseBuilder->addResponseMessage($message);

        return $request->addMessage($message);
    }

    protected function handleStop(Request $request): StructuredResponse
    {
        $this->ensureStepsNotExceeded($request);

        $request = $this->appendMessageForJsonMode($request);
        $response = $this->sendRequest($request);

        $request = $this->handleResponseMessage($request, $response);
        $this->addResponseStep($request, $response);

        return $this->responseBuilder->toResponse();
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new UserMessage(sprintf(
            "Respond with JSON that matches the following schema: \n\n %s \n\n DO NOT include additional text",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
