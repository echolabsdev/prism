<?php

namespace EchoLabs\Prism\Providers\VoyageAI;

use EchoLabs\Prism\Embeddings\Request as EmbeddingsRequest;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Embedding;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

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
        $providerMeta = $this->request->providerMeta('voyage');

        try {
            $this->httpResponse = $this->client->post('embeddings', array_filter([
                'model' => $this->request->model(),
                'input' => $this->request->inputs(),
                'input_type' => $providerMeta['input_type'] ?? null,
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
