<?php

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Gemini\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;
use EchoLabs\Prism\Providers\Gemini\Maps\SchemaMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response as StructuredResponse;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Structured
{
    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): StructuredResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }

        $data = $response->json();

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: [%s] %s',
                [
                    data_get($data, 'error.code', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        $text = data_get($data, 'candidates.0.content.parts.0.text') ?? '';

        $responseMessage = new AssistantMessage($text);
        $this->responseBuilder->addResponseMessage($responseMessage);
        $request->addMessage($responseMessage);

        $this->responseBuilder->addStep(
            new Step(
                text: $text,
                finishReason: FinishReasonMap::map(
                    data_get($data, 'candidates.0.finishReason'),
                ),
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
            )
        );

        return $this->responseBuilder->toResponse();
    }

    public function sendRequest(Request $request): Response
    {
        $endpoint = "{$request->model()}:generateContent";

        $payload = (new MessageMap($request->messages(), $request->systemPrompts()))();

        $responseSchema = new SchemaMap($request->schema());

        $payload['generationConfig'] = array_merge([
            'response_mime_type' => 'application/json',
            'response_schema' => $responseSchema->toArray(),
        ], array_filter([
            'temperature' => $request->temperature(),
            'topP' => $request->topP(),
            'maxOutputTokens' => $request->maxTokens(),
        ]));

        $safetySettings = $request->providerMeta(Provider::Gemini, 'safetySettings');
        if (! empty($safetySettings)) {
            $payload['safetySettings'] = $safetySettings;
        }

        return $this->client->post($endpoint, $payload);
    }
}
