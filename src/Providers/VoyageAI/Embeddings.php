<?php

namespace PrismPHP\Prism\Providers\VoyageAI;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use PrismPHP\Prism\Embeddings\Request as EmbeddingsRequest;
use PrismPHP\Prism\Embeddings\Response as EmbeddingsResponse;
use PrismPHP\Prism\Enums\Provider;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\ValueObjects\Embedding;
use PrismPHP\Prism\ValueObjects\EmbeddingsUsage;

class Embeddings
{
    protected EmbeddingsRequest $request;

    protected Response $httpResponse;

    public function __construct(protected PendingRequest $client) {}

    public function handle(EmbeddingsRequest $request): EmbeddingsResponse
    {
        $this->request = $request;

        $this->sendRequest();

        $this->validateResponse();

        $data = $this->httpResponse->json();

        return new EmbeddingsResponse(
            embeddings: array_map(fn (array $item): Embedding => Embedding::fromArray($item['embedding']), data_get($data, 'data', [])),
            usage: new EmbeddingsUsage(
                tokens: data_get($data, 'usage.total_tokens', null),
            ),
        );
    }

    protected function sendRequest(): void
    {
        $providerMeta = $this->request->providerMeta(Provider::VoyageAI);

        try {
            $this->httpResponse = $this->client->post('embeddings', array_filter([
                'model' => $this->request->model(),
                'input' => $this->request->inputs(),
                'input_type' => $providerMeta['inputType'] ?? null,
                'truncation' => $providerMeta['truncation'] ?? null,
            ]));
        } catch (\Exception $e) {
            throw PrismException::providerRequestError($this->request->model(), $e);
        }
    }

    protected function validateResponse(): void
    {
        $data = $this->httpResponse->json();

        if (! $data || data_get($data, 'detail')) {
            throw PrismException::providerResponseError('Voyage AI error: '.data_get($data, 'detail'));
        }
    }
}
