<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Mistral\Handlers;

use Throwable;
use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Embeddings\Request;
use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\ValueObjects\Embedding;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\EmbeddingsUsage;
use PrismPHP\Prism\Embeddings\Response as EmbeddingsResponse;
use PrismPHP\Prism\Providers\Ollama\Concerns\ValidatesResponse;

class Embeddings
{
    use ValidatesResponse;

    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): EmbeddingsResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }

        $this->validateResponse($response);

        $data = $response->json();

        if (! $data || data_get($data, 'object') === 'error') {
            throw PrismException::providerResponseError(vsprintf(
                'Mistral Error:  [%s] %s',
                [
                    data_get($data, 'type', 'unknown'),
                    data_get($data, 'message', 'unknown'),
                ]
            ));
        }

        return new EmbeddingsResponse(
            embeddings: array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item['embedding']), data_get($data, 'data', [])),
            usage: new EmbeddingsUsage(data_get($data, 'usage.total_tokens', null)),
            rateLimits: $this->processRateLimits($response),
        );
    }

    protected function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'embeddings',
            [
                'model' => $request->model(),
                'input' => $request->inputs(),
            ]
        );
    }
}
