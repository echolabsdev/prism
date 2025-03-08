<?php

namespace PrismPHP\Prism\Providers\OpenAI\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Enums\StructuredMode;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\OpenAI\Concerns\MapsFinishReason;
use PrismPHP\Prism\Providers\OpenAI\Concerns\ValidatesResponses;
use PrismPHP\Prism\Providers\OpenAI\Maps\MessageMap;
use PrismPHP\Prism\Providers\OpenAI\Support\StructuredModeResolver;
use PrismPHP\Prism\Structured\Request;
use PrismPHP\Prism\Structured\Response as StructuredResponse;
use PrismPHP\Prism\Structured\ResponseBuilder;
use PrismPHP\Prism\Structured\Step;
use PrismPHP\Prism\ValueObjects\Messages\AssistantMessage;
use PrismPHP\Prism\ValueObjects\Messages\SystemMessage;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\Usage;
use Throwable;

class Structured
{
    use MapsFinishReason;
    use ValidatesResponses;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): StructuredResponse
    {
        $data = match ($request->mode()) {
            StructuredMode::Auto => $this->handleAutoMode($request),
            StructuredMode::Structured => $this->handleStructuredMode($request),
            StructuredMode::Json => $this->handleJsonMode($request),

        };

        $this->validateResponse($data);
        $this->handleRefusal(data_get($data, 'choices.0.message', []));

        $responseMessage = new AssistantMessage(
            data_get($data, 'choices.0.message.content') ?? '',
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        $this->addStep($data, $request);

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addStep(array $data, Request $request): void
    {
        $this->responseBuilder->addStep(new Step(
            text: data_get($data, 'choices.0.message.content') ?? '',
            finishReason: $this->mapFinishReason($data),
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

    /**
     * @param  array{type: 'json_schema', json_schema: array<string, mixed>}|array{type: 'json_object'}  $responseFormat
     * @return array<string, mixed>
     */
    protected function sendRequest(Request $request, array $responseFormat): array
    {
        try {
            $response = $this->client->post(
                'chat/completions',
                array_merge([
                    'model' => $request->model(),
                    'messages' => (new MessageMap($request->messages(), $request->systemPrompts()))(),
                    'max_completion_tokens' => $request->maxTokens(),
                ], array_filter([
                    'temperature' => $request->temperature(),
                    'top_p' => $request->topP(),
                    'response_format' => $responseFormat,
                ]))
            );

            return $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleAutoMode(Request $request): array
    {
        $mode = StructuredModeResolver::forModel($request->model());

        return match ($mode) {
            StructuredMode::Structured => $this->handleStructuredMode($request),
            StructuredMode::Json => $this->handleJsonMode($request),
            default => throw new PrismException('Could not determine structured mode for your request'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleStructuredMode(Request $request): array
    {
        $mode = StructuredModeResolver::forModel($request->model());

        if ($mode !== StructuredMode::Structured) {
            throw new PrismException(sprintf('%s model does not support structured mode', $request->model()));
        }

        return $this->sendRequest($request, [
            'type' => 'json_schema',
            'json_schema' => array_filter([
                'name' => $request->schema()->name(),
                'schema' => $request->schema()->toArray(),
                'strict' => (bool) $request->providerMeta(Provider::OpenAI, 'schema.strict'),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function handleJsonMode(Request $request): array
    {
        $request = $this->appendMessageForJsonMode($request);

        return $this->sendRequest($request, [
            'type' => 'json_object',
        ]);
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
            json_encode($request->schema()->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
