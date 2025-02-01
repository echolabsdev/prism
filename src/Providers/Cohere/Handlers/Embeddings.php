<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Cohere\Handlers;

use EchoLabs\Prism\Embeddings\Request;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Embeddings
{
    /**
     * @param array<string, string> $config
     * @param PendingRequest $client
     */
    public function __construct(protected array $config, protected PendingRequest $client)
    {
    }

    /**
     * @param Request $request
     * @return EmbeddingsResponse
     * @throws PrismException
     */
    public function handle(Request $request): EmbeddingsResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();
        if (isset($data['message'])) {
            throw PrismException::providerResponseError(sprintf('Cohere Error: %s', $data['message']));
        }

        return new EmbeddingsResponse(
            embeddings: data_get($data, sprintf('embeddings.%s.0', $this->config['output']), []),
            usage: new EmbeddingsUsage(data_get($data, 'meta.billed_units.input_tokens', null)),
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ConnectionException
     */
    protected function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'embed',
            [
                'model' => $request->model,
                'input_type' => $this->config['input'],
                'embedding_types' => [$this->config['output']],
                'texts' => [$request->input],
            ]
        );
    }
}
