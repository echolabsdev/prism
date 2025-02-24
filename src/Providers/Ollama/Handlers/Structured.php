<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Concerns\MapsFinishReason;
use EchoLabs\Prism\Providers\Ollama\Concerns\ValidatesResponse;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Structured\Request;
use EchoLabs\Prism\Structured\Response;
use EchoLabs\Prism\Structured\ResponseBuilder;
use EchoLabs\Prism\Structured\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Fluent;
use Throwable;

class Structured
{
    use MapsFinishReason;
    use ValidatesResponse;

    protected ResponseBuilder $responseBuilder;

    public function __construct(protected PendingRequest $client)
    {
        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(Request $request): Response
    {
        $data = $this->sendRequest($request);

        $this->validateResponse($data);

        $responseMessage = new AssistantMessage(
            $data->get('message.content', ''),
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        $this->addStep($data, $request);

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  Fluent<string, mixed>  $data
     */
    protected function addStep(Fluent $data, Request $request): void
    {
        $this->responseBuilder->addStep(new Step(
            text: $data->get('message.content', ''),
            finishReason: $this->mapFinishReason($data),
            usage: new Usage(
                $data->get('prompt_eval_count', 0),
                $data->get('eval_count', 0),
            ),
            responseMeta: new ResponseMeta(
                id: '',
                model: $request->model(),
            ),
            messages: $request->messages(),
            systemPrompts: $request->systemPrompts(),
            additionalContent: [],
        ));
    }

    /**
     * @return Fluent<string, mixed>
     */
    protected function sendRequest(Request $request): Fluent
    {
        if (count($request->systemPrompts()) > 1) {
            throw new PrismException('Ollama does not support multiple system prompts using withSystemPrompt / withSystemPrompts. However, you can provide additional system prompts by including SystemMessages in with withMessages.');
        }

        try {
            return $this->client->post('api/chat', [
                'model' => $request->model(),
                'system' => data_get($request->systemPrompts(), '0.content', ''),
                'messages' => (new MessageMap($request->messages()))->map(),
                'format' => $request->schema()->toArray(),
                'stream' => false,
                'options' => array_filter([
                    'temperature' => $request->temperature(),
                    'num_predict' => $request->maxTokens() ?? 2048,
                    'top_p' => $request->topP(),
                ])])->fluent();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }
}
