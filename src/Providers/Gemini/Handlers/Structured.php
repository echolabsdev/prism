<?php

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Gemini\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
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
            $request = $this->appendMessageForJsonMode($request);
            $response = $this->sendRequest($request);
            $this->validateResponse($response);

            return $this->createResponse($response);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }
    }

    public function sendRequest(Request $request): Response
    {
        $endpoint = "{$request->model}:generateContent";

        $payload = (new MessageMap($request->messages, $request->systemPrompt))();

	    $payload['generationConfig'] = array_filter([
            'temperature' => $request->temperature,
            'topP' => $request->topP,
            'maxOutputTokens' => $request->maxTokens,
            'response_mime_type' => 'application/json',
        ]);

        $safetySettings = data_get($request->providerMeta, 'safetySettings');
        if (! empty($safetySettings)) {
            $payload['safetySettings'] = $safetySettings;
        }

        return $this->client->post($endpoint, $payload);
    }

    protected function validateResponse(Response $response): void
    {
        $data = $response->json();

        if (! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: %s',
                [
                    (string) $response->getBody(),
                ]
            ));
        }
    }

    protected function createResponse(Response $response): ProviderResponse
    {
        $data = $response->json();

        return new ProviderResponse(
            text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
            toolCalls: [],
            usage: new Usage(
                data_get($data, 'usageMetadata.promptTokenCount', 0),
                data_get($data, 'usageMetadata.candidatesTokenCount', 0)
            ),
            finishReason: FinishReasonMap::map(
                data_get($data, 'candidates.0.finishReason')
            ),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id', ''),
                model: data_get($data, 'modelVersion'),
            )
        );
    }

    protected function appendMessageForJsonMode(Request $request): Request
    {
        return $request->addMessage(new UserMessage(sprintf(
            "Respond with ONLY JSON that matches the following schema: \n %s",
            json_encode($request->schema->toArray(), JSON_PRETTY_PRINT)
        )));
    }
}
