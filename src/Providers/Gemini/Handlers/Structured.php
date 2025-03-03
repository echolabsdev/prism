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
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
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
        $data = $this->sendRequest($request);

        $this->validateResponse($data);

        $responseMessage = new AssistantMessage(data_get($data, 'candidates.0.content.parts.0.text') ?? '');

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        $this->addStep($data, $request);

        return $this->responseBuilder->toResponse();
    }

    /**
     * @return array<string, mixed>
     */
    public function sendRequest(Request $request): array
    {
        try {
            $providerMeta = $request->providerMeta(Provider::Gemini);

            $response = $this->client->post(
                "{$request->model()}:generateContent",
                array_filter([
                    ...(new MessageMap($request->messages(), $request->systemPrompts()))(),
                    'cachedContent' => $providerMeta['cachedContentName'] ?? null,
                    'generationConfig' => array_filter([
                        'response_mime_type' => 'application/json',
                        'response_schema' => new SchemaMap($request->schema()),
                        'temperature' => $request->temperature(),
                        'topP' => $request->topP(),
                        'maxOutputTokens' => $request->maxTokens(),
                    ]),
                    'safetySettings' => $providerMeta['safetySettings'] ?? null,
                ])
            );

            return $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateResponse(array $data): void
    {
        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: [%s] %s',
                [
                    data_get($data, 'error.code', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function addStep(array $data, Request $request): void
    {
        $this->responseBuilder->addStep(
            new Step(
                text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
                finishReason: FinishReasonMap::map(
                    data_get($data, 'candidates.0.finishReason'),
                ),
                usage: new Usage(
                    data_get($data, 'usageMetadata.promptTokenCount', 0),
                    data_get($data, 'usageMetadata.candidatesTokenCount', 0)
                ),
                responseMeta: new ResponseMeta(
                    id: data_get($data, 'id', ''),
                    model: data_get($data, 'modelVersion'),
                ),
                messages: $request->messages(),
                systemPrompts: $request->systemPrompts(),
            )
        );
    }
}
