<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Gemini\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Gemini\Maps\MessageMap;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Text
{
    public function __construct(
        protected PendingRequest $client,
        #[\SensitiveParameter] protected string $apiKey,
    ) {}

    public function handle(Request $request): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
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

        return new ProviderResponse(
            text: data_get($data, 'candidates.0.content.parts.0.text') ?? '',
            toolCalls: [],
            usage: new Usage(
                data_get($data, 'usageMetadata.promptTokenCount', 0),
                data_get($data, 'usageMetadata.candidatesTokenCount', 0)
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'candidates.0.finishReason')),
            response: [
                'id' => data_get($data, 'id'),
                'model' => data_get($data, 'modelVersion'),
            ]
        );
    }

    protected function sendRequest(Request $request): Response
    {
        $endpoint = "{$request->model}:generateContent";

        $payload = (new MessageMap($request->messages, $request->systemPrompt))();

        $generationConfig = array_filter([
            'temperature' => $request->temperature,
            'topP' => $request->topP,
            'maxOutputTokens' => $request->maxTokens,
        ]);

        if ($generationConfig !== []) {
            $payload['generationConfig'] = $generationConfig;
        }

        $safetySettings = data_get($request->providerMeta, 'safetySettings');
        if (! empty($safetySettings)) {
            $payload['safetySettings'] = $safetySettings;
        }

        return $this->client->post($endpoint, $payload);
    }
}
