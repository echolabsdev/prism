<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

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

        if (data_get($data, 'error') || ! $data) {
            throw PrismException::providerResponseError(vsprintf(
                'Ollama Error:  [%s] %s',
                [
                    data_get($data, 'error.type', 'unknown'),
                    data_get($data, 'error.message', 'unknown'),
                ]
            ));
        }

        return new EmbeddingsResponse(
            embeddings: data_get($data, 'data.0.embedding', []),
            usage: new EmbeddingsUsage(data_get($data, 'usage.total_tokens', null)),
        );
    }

    protected function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'embeddings',
            [
                'model' => $request->model,
                'input' => $request->input,
            ]
        );
    }
}
