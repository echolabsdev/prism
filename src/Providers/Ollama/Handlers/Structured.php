<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use EchoLabs\Prism\ValueObjects\ProviderResponse;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Throwable;

class Structured
{
    public function __construct(protected PendingRequest $client) {}

    public function handle(Request $request): ProviderResponse
    {
        try {
            $response = $this->sendRequest($request);
            $data = $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model, $e);
        }

        if (! $data || data_get($data, 'error')) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: %s',
                data_get($data, 'error', 'unknown'),
            ));
        }

        return new ProviderResponse(
            text: data_get($data, 'message.content') ?? '',
            toolCalls: [],
            usage: new Usage(
                data_get($data, 'prompt_eval_count', 0),
                data_get($data, 'eval_count', 0),
            ),
            finishReason: FinishReasonMap::map(data_get($data, 'done_reason', '')),
            responseMeta: new ResponseMeta(
                id: '',
                model: $request->model,
            )
        );
    }

    public function sendRequest(Request $request): Response
    {
        $messages = $request->messages;

        // Remove first message if it is a system message with the same content as the request system prompt
        if ($messages[0] instanceof SystemMessage && $messages[0]->content === $request->systemPrompt) {
            array_shift($messages);
        }

        return $this->client->post('api/chat', ['model' => $request->model, 'system' => $request->systemPrompt, 'messages' => (new MessageMap($messages))->map(), 'format' => $request->schema->toArray(), 'stream' => false, 'options' => array_filter([
            'temperature' => $request->temperature,
            'num_predict' => $request->maxTokens ?? 2048,
            'top_p' => $request->topP,
        ])]);
    }
}
