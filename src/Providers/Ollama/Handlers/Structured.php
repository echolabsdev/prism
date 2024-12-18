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
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
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

    public function sendRequest(Request $request, bool $withSchema = false): ProviderResponse
    {
        try {
            $response = $this->client->post(
                'chat/completions',
                $this->buildRequestPayload($request, $withSchema)
            );

            return $this->buildProviderResponse($response->json());
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRequestPayload(Request $request, bool $withSchema = false): array
    {
        $payload = [
            'model' => $request->model,
            'messages' => $this->buildMessages($request),
            'max_tokens' => $request->maxTokens ?? 2048,
        ];

        return array_merge(
            $payload,
            $this->buildOptionalParameters($request),
            $this->buildSchemaFormat($request, $withSchema)
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildMessages(Request $request): array
    {
        return (new MessageMap(
            $request->messages,
            $request->systemPrompt ?? ''
        ))();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildOptionalParameters(Request $request): array
    {
        return array_filter([
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'tools' => ToolMap::map($request->tools),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSchemaFormat(Request $request, bool $withSchema): array
    {
        if (! $withSchema) {
            return [];
        }

        return [
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => $request->schema->name(),
                    'schema' => $request->schema->toArray(),
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildProviderResponse(array $data): ProviderResponse
    {
        $this->validateResponseData($data);

        return new ProviderResponse(
            text: data_get($data, 'choices.0.message.content') ?? '',
            toolCalls: $this->buildToolCalls($data),
            usage: $this->buildUsage($data),
            finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', '')),
            meta: new Meta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponseData(array $data): void
    {
        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: [%s] %s',
                data_get($data, 'error.type', 'unknown'),
                data_get($data, 'error.message', 'unknown'),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, ToolCall>
     */
    protected function buildToolCalls(array $data): array
    {
        $toolCalls = data_get($data, 'choices.0.message.tool_calls', []) ?? [];

        return array_map(fn (array $toolCall): ToolCall => new ToolCall(
            id: data_get($toolCall, 'id'),
            name: data_get($toolCall, 'function.name'),
            arguments: data_get($toolCall, 'function.arguments'),
        ), $toolCalls);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildUsage(array $data): Usage
    {
        return new Usage(
            data_get($data, 'usage.prompt_tokens'),
            data_get($data, 'usage.completion_tokens')
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{id: string, model: string}
     */
    protected function buildResponseMetadata(array $data): array
    {
        return [
            'id' => (string) data_get($data, 'id'),
            'model' => (string) data_get($data, 'model'),
        ];
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

    /**
     * @param  array<int, ToolResult>  $toolResults
     */
    protected function addToolResultMessage(Request $request, array $toolResults): Request
    {
        $message = new ToolResultMessage($toolResults);
        $this->responseBuilder->addResponseMessage($message);

        return $request->addMessage($message);
    }

    /**
     * @param  array<int, ToolResult>  $toolResults
     */
    protected function addResponseStep(Request $request, ProviderResponse $response, array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults,
            usage: $response->usage,
            meta: $response->meta,
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

        $response = $this->sendRequest($request, withSchema: true);

        $request = $this->handleResponseMessage($request, $response);
        $this->addResponseStep($request, $response);

        return $this->responseBuilder->toResponse();
    }
}
