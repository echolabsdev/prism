<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Ollama\Handlers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Embeddings\Request;
use PrismPHP\Prism\Embeddings\Response as EmbeddingsResponse;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\Embedding;
use PrismPHP\Prism\ValueObjects\EmbeddingsUsage;
use Throwable;

class Embeddings
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): EmbeddingsResponse
    {
        try {
            $response = $this->sendRequest($request);
            $data = $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: %s',
                data_get($data, 'error', 'unknown'),
            ));
        }

        return new EmbeddingsResponse(
            embeddings: array_map(fn (array $item): \PrismPHP\Prism\ValueObjects\Embedding => Embedding::fromArray($item), data_get($data, 'embeddings', [])),
            usage: new EmbeddingsUsage(data_get($data, 'prompt_eval_count', null)),
        );
    }

    protected function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'api/embed',
            [
                'model' => $request->model(),
                'input' => $request->inputs(),
            ]
        );
    }
}
