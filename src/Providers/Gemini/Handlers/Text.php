<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;
use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;

class Text
{
    public function __construct(
        protected PendingRequest $client,
        protected string $apiKey,
    ) {}

    public function handle(Request $request): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();

        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'Gemini Error: [%s] %s',
                [
                    data_get($data, 'error.code', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        return new ProviderResponse(
            text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
            toolCalls: [],
            usage: new Usage(
                data_get($data, 'usageMetadata.promptTokenCount'),
                data_get($data, 'usageMetadata.candidatesTokenCount'),
            ),
            finishReason: data_get($data, 'candidates.0.finishReason', ''),
            response: array_merge(
                [
                    'avgLogprobs' => data_get($data, 'candidates.0.avgLogprobs'),
                    'model' => data_get($data, 'modelVersion'),
                ]
            )
        );
    }

    protected function sendRequest(Request $request): Response
    {
        $endpoint = sprintf('%s:generateContent', $request->model);
        
        $payload = array_merge(
            (new MessageMap($request->messages, $request->systemPrompt))(),
            [
                'generationConfig' => array_filter([
                    'temperature' => $request->temperature,
                    'topP' => $request->topP,
                    'maxOutputTokens' => $request->maxTokens,
                ]),
                'safetySettings' => data_get($request->providerMeta, 'safetySettings', null),
            ]
        );
        
        return $this->client->post($endpoint . '?key=' . $this->apiKey, $payload);
    }
} 
