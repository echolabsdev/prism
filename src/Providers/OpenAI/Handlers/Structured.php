<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\OpenAI\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\OpenAI\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolCallMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\ToolMap;
use EchoLabs\Prism\Providers\OpenAI\Support\StructuredModeResolver;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
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

        $mode = StructuredModeResolver::forModel($request->model);

        if ($mode === StructuredMode::Json) {
            $request = $this->appendMessageForJsonMode($request);
        }

        $response = $this->sendRequest($request);
        $request = $this->handleResponseMessage($request, $response);

        return match ($response->finishReason) {
            FinishReason::ToolCalls => $this->handleToolCalls($request, $response),
            FinishReason::Stop => $this->handleStop($request, $response),
            default => throw PrismException::providerResponseError('Unexpected finish reason')
        };
    }

    public function sendRequest(Request $request): ProviderResponse
    {
        try {
            $response = $this->client->post(
                'chat/completions',
                $this->buildRequestPayload($request)
            );

            $data = $response->json();
            $this->validateResponseData($data);
            $this->handleRefusal(data_get($data, 'choices.0.message', []));

            return $this->buildProviderResponse($data);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRequestPayload(Request $request): array
    {
        $payload = [
            'model' => $request->model,
            'messages' => $this->buildMessages($request),
            'max_completion_tokens' => $request->maxTokens ?? 2048,
        ];

        return array_merge(
            $payload,
            $this->buildOptionalParameters($request),
            ['response_format' => $this->mapResponseFormat($request)]
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
            'tool_choice' => ToolChoiceMap::map($request->toolChoice),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponseData(array $data): void
    {
        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(sprintf(
                'OpenAI Error: [%s] %s',
                data_get($data, 'error.type', 'unknown'),
                data_get($data, 'error.message', 'unknown'),
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function buildProviderResponse(array $data): ProviderResponse
    {
        return new ProviderResponse(
            text: data_get($data, 'choices.0.message.content') ?? '',
            toolCalls: ToolCallMap::map(data_get($data, 'choices.0.message.tool_calls', [])),
            usage: new Usage(
                data_get($data, 'usage.prompt_tokens'),
                data_get($data, 'usage.completion_tokens')
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', '')),
            response: [
                'id' => (string) data_get($data, 'id'),
                'model' => (string) data_get($data, 'model'),
            ]
        );
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

    protected function handleStop(Request $request, ProviderResponse $response): StructuredResponse
    {
        $this->addResponseStep($request, $response);

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  array<string, string>  $message
     */
    protected function handleRefusal(array $message): void
    {
        if (! is_null(data_get($message, 'refusal', null))) {
            throw new PrismException(sprintf('OpenAI Refusal: %s', $message['refusal']));
        }
    }

    /**
     * @return array{type: 'json_schema', json_schema: array<string, mixed>}|array{type: 'json_object'}|null
     */
    protected function mapResponseFormat(Request $request): ?array
    {
        $mode = StructuredModeResolver::forModel($request->model);

        if ($mode === StructuredMode::Structured) {
            return [
                'type' => 'json_schema',
                'json_schema' => array_filter([
                    'name' => $request->schema->name(),
                    'schema' => $request->schema->toArray(),
                    'strict' => $request->providerMeta(Provider::OpenAI, 'schema.strict'),
                ]),
            ];
        }

        return [
            'type' => 'json_object',
        ];
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new SystemMessage(sprintf(
            "Respond with JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
