<?php

namespace EchoLabs\Prism\Providers\OpenAI\Handlers;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\StructuredMode;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\OpenAI\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\OpenAI\Maps\MessageMap;
use EchoLabs\Prism\Providers\OpenAI\Support\StructuredModeResolver;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Structured
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): ProviderResponse
    {
        try {
            return match ($request->mode) {
                StructuredMode::Auto => $this->handleAutoMode($request),
                StructuredMode::Structured => $this->handleStructuredMode($request),
                StructuredMode::Json => $this->handleJsonMode($request),

            };
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

    }

    /**
     * @param  array{type: 'json_schema', json_schema: array<string, mixed>}|array{type: 'json_object'}  $responseFormat
     */
    public function sendRequest(Request $request, array $responseFormat): Response
    {
        return $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model,
                'messages' => (new MessageMap($request->messages, $request->systemPrompt ?? ''))(),
                'max_completion_tokens' => $request->maxTokens ?? 2048,
            ], array_filter([
                'temperature' => $request->temperature,
                'top_p' => $request->topP,
                'response_format' => $responseFormat,
            ]))
        );
    }

    protected function handleAutoMode(Request $request): ProviderResponse
    {
        $mode = StructuredModeResolver::forModel($request->model);

        return match ($mode) {
            StructuredMode::Structured => $this->handleStructuredMode($request),
            StructuredMode::Json => $this->handleJsonMode($request),
            default => throw new PrismException('Could not determine structured mode for your request'),
        };
    }

    protected function handleStructuredMode(Request $request): ProviderResponse
    {
        $mode = StructuredModeResolver::forModel($request->model);

        if ($mode !== StructuredMode::Structured) {
            throw new PrismException(sprintf('%s model does not support structured mode', $request->model));
        }

        $response = $this->sendRequest($request, [
            'type' => 'json_schema',
            'json_schema' => array_filter([
                'name' => $request->schema->name(),
                'schema' => $request->schema->toArray(),
                'strict' => $request->providerMeta(Provider::OpenAI, 'schema.strict'),
            ]),
        ]);

        $this->validateResponse($response);

        return $this->createResponse($response);
    }

    protected function handleJsonMode(Request $request): ProviderResponse
    {
        $request = $this->appendMessageForJsonMode($request);

        $response = $this->sendRequest($request, [
            'type' => 'json_object',
        ]);

        $this->validateResponse($response);

        return $this->createResponse($response);
    }

    protected function validateResponse(Response $response): void
    {
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

        $this->handleRefusal(data_get($data, 'choices.0.message', []));
    }

    protected function createResponse(Response $response): ProviderResponse
    {
        $data = $response->json();

        return new ProviderResponse(
            text: data_get($data, 'choices.0.message.content') ?? '',
            toolCalls: [],
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

    /**
     * @param  array<string, string>  $message
     */
    protected function handleRefusal(array $message): void
    {
        if (! is_null(data_get($message, 'refusal', null))) {
            throw new PrismException(sprintf('OpenAI Refusal: %s', $message['refusal']));
        }
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new SystemMessage(sprintf(
            "Respond with JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
