<?php

namespace EchoLabs\Prism\Providers\DeepSeek\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\DeepSeek\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\DeepSeek\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Structured
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): StructuredResponse
    {
        try {
            $request = $this->appendMessageForJsonMode($request);

            $response = $this->sendRequest($request);

            $this->validateResponse($response);

            return $this->createResponse($request, $response);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
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
    }

    protected function validateResponse(Response $response): void
    {
        $data = $response->json();

        if (! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'DeepSeek Error: %s',
                [
                    (string) $response->getBody(),
                ]
            ));
        }
    }

    protected function createResponse(Request $request, Response $response): StructuredResponse
    {
        $data = $response->json();
        $responseBuilder = new ResponseBuilder;

        $step = new Step(
            text: data_get($data, 'choices.0.message.content') ?? '',
            object: json_decode(data_get($data, 'choices.0.message.content') ?? '', true),
            finishReason: FinishReasonMap::map(data_get($data, 'choices.0.finish_reason', '')),
            usage: new Usage(
                data_get($data, 'usage.prompt_tokens'),
                data_get($data, 'usage.completion_tokens'),
            ),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
            ),
            messages: $request->messages,
            additionalContent: [],
        );

        $responseBuilder->addStep($step);

        foreach ($request->messages as $message) {
            $responseBuilder->addResponseMessage($message);
        }

        return $responseBuilder->toResponse();
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new SystemMessage(sprintf(
            "Respond with JSON that matches the following schema: \n %s",
            json_encode($request->schema()->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
