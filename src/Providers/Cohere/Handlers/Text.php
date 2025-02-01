<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Cohere\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Cohere\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Cohere\Maps\MessageMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Text
{
    use Handler;

    /**
     * @param PendingRequest $client
     */
    public function __construct(protected PendingRequest $client)
    {
    }

    /**
     * @param Request $request
     * @return ProviderResponse
     * @throws PrismException
     */
    public function handle(Request $request): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request);
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        $data = $response->json();
        if (isset($data['message']) && is_string($data['message'])) {
            throw PrismException::providerResponseError(sprintf('Cohere Error: %s', $data['message']));
        }

        return new ProviderResponse(
            text: data_get($data, 'message.content.0.text') ?? '',
            toolCalls: $this->mapToolCalls(data_get($data, 'message.tool_calls', []) ?? []),
            usage: new Usage(
                data_get($data, 'usage.billed_units.input_tokens'),
                data_get($data, 'usage.billed_units.output_tokens'),
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'finish_reason', '')),
            responseMeta: new ResponseMeta(
                id: data_get($data, 'id'),
                model: $request->model,
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ConnectionException
     */
    public function sendRequest(Request $request): Response
    {
        return $this->client->post(
            'chat',
            [
                'stream' => false,
                'model' => $request->model,
                'messages' => (new MessageMap($request->messages, $request->systemPrompt ?? ''))()
            ]
        );
    }
}
