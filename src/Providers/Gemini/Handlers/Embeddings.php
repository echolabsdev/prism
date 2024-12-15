<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Gemini\Handlers;

use EchoLabs\Prism\Embeddings\Request;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Embeddings
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): EmbeddingsResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();

        if (! isset($data['embedding'])) {
            throw PrismException::providerResponseError(
                'Gemini Error: Invalid response format or missing embedding data'
            );
        }

        return new EmbeddingsResponse(
            embeddings: $data['embedding']['values'] ?? [],
            usage: new EmbeddingsUsage(0) // Gemini doesn't provide token usage info
        );
    }

    protected function sendRequest(Request $request): Response
    {
        return $this->client->post(
            "{$request->model}:embedContent",
            [
                'model' => $request->model,
                'content' => [
                    'parts' => [
                        ['text' => $request->input],
                    ],
                ],
            ]
        );
    }
}
