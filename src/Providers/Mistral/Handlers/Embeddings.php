<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Mistral\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Embeddings\Request;
use PrismPHP\Prism\Embeddings\Response as EmbeddingsResponse;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\Mistral\Concerns\ValidatesResponse;
use PrismPHP\Prism\ValueObjects\Embedding;
use PrismPHP\Prism\ValueObjects\EmbeddingsUsage;
use PrismPHP\Prism\ValueObjects\Meta;
use Throwable;

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

        return new EmbeddingsResponse(
            embeddings: array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item['embedding']), data_get($data, 'data', [])),
            usage: new EmbeddingsUsage(data_get($data, 'usage.total_tokens', null)),
            meta: new Meta(
                id: data_get($data, 'id', ''),
                model: data_get($data, 'model', ''),
                rateLimits: $this->processRateLimits($response)
            )
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
