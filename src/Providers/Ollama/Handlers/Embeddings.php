<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Embeddings\Request;
use EchoLabs\Prism\Embeddings\Response as EmbeddingsResponse;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\EmbeddingsUsage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Fluent;
use Throwable;

class Embeddings
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): EmbeddingsResponse
    {
        try {
            $data = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }

        if ($data->has('error')) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: %s',
                $data->get('error', 'unknown')
            ));
        }

        return new EmbeddingsResponse(
            embeddings: $data->get('embeddings', []),
            usage: new EmbeddingsUsage($data->get('prompt_eval_count')),
        );
    }

    /**
     * @return Fluent<string, mixed>
     *
     * @throws ConnectionException
     */
    protected function sendRequest(Request $request): Fluent
    {
        return $this->client->post(
            'api/embed',
            [
                'model' => $request->model(),
                'input' => $request->input(),
            ]
        )->fluent();
    }
}
