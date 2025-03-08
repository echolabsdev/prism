<?php

namespace PrismPHP\Prism\Providers\DeepSeek\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\DeepSeek\Concerns\MapsFinishReason;
use PrismPHP\Prism\Providers\DeepSeek\Concerns\ValidatesResponses;
use PrismPHP\Prism\Providers\DeepSeek\Maps\FinishReasonMap;
use PrismPHP\Prism\Providers\DeepSeek\Maps\MessageMap;
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
        try {
            $request = $this->appendMessageForJsonMode($request);

            $data = $this->sendRequest($request);

            $this->validateResponse($data);

            return $this->createResponse($request, $data);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendRequest(Request $request): array
    {
        $response = $this->client->post(
            'chat/completions',
            array_merge([
                'model' => $request->model(),
                'messages' => (new MessageMap($request->messages(), $request->systemPrompts()))(),
                'max_completion_tokens' => $request->maxTokens(),
            ], array_filter([
                'temperature' => $request->temperature(),
                'top_p' => $request->topP(),
                'response_format' => ['type' => 'json_object'],
            ]))
        );

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if ($data === []) {
            throw PrismException::providerResponseError('DeepSeek Error: Empty response');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createResponse(Request $request, array $data): StructuredResponse
    {
        $text = data_get($data, 'choices.0.message.content') ?? '';

        $responseMessage = new AssistantMessage($text);
        $this->responseBuilder->addResponseMessage($responseMessage);
        $request->addMessage($responseMessage);

        $step = new Step(
            text: $text,
            finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', '')),
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
        );

        $this->responseBuilder->addStep($step);

        return $this->responseBuilder->toResponse();
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new SystemMessage(sprintf(
            "Respond with JSON that matches the following schema: \n %s",
            json_encode($request->schema()->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
