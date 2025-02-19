<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Ollama\Concerns\MapsFinishReason;
use EchoLabs\Prism\Providers\Ollama\Concerns\MapsToolCalls;
use EchoLabs\Prism\Providers\Ollama\Concerns\ValidatesResponse;
use EchoLabs\Prism\Providers\Ollama\Maps\MessageMap;
use EchoLabs\Prism\Providers\Ollama\Maps\ToolMap;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\Text\Response;
use EchoLabs\Prism\Text\ResponseBuilder;
use EchoLabs\Prism\Text\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\ResponseMeta;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

class Text
{
    use CallsTools;
    use MapsFinishReason;
    use MapsToolCalls;
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
            data_get($data, 'message.content') ?? '',
            $this->mapToolCalls(data_get($data, 'message.tool_calls', [])),
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $request->addMessage($responseMessage);

        return match ($this->mapFinishReason($data)) {
            FinishReason::ToolCalls => $this->handleToolCalls($data, $request),
            FinishReason::Stop => $this->handleStop($data, $request),
            default => throw new PrismException('Ollama: unknown finish reason'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function sendRequest(Request $request): array
    {
        try {
            $response = $this
                ->client
                ->post('api/chat', [
                    'model' => $request->model(),
                    'system' => $request->systemPrompt(),
                    'messages' => (new MessageMap($request->messages()))->map(),
                    'tools' => ToolMap::map($request->tools()),
                    'stream' => false,
                    'options' => array_filter([
                        'temperature' => $request->temperature(),
                        'num_predict' => $request->maxTokens() ?? 2048,
                        'top_p' => $request->topP(),
                    ])]);

            return $response->json();
        } catch (Throwable $e) {
            throw PrismException::providerRequestError($request->model(), $e);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleToolCalls(array $data, Request $request): Response
    {
        $toolResults = $this->callTools(
            $request->tools(),
            $this->mapToolCalls(data_get($data, 'message.tool_calls', [])),
        );

        $request->addMessage(new ToolResultMessage($toolResults));

        $this->addStep($data, $request, $toolResults);

        if ($this->shouldContinue($request)) {
            return $this->handle($request);
        }

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleStop(array $data, Request $request): Response
    {
        $this->addStep($data, $request);

        return $this->responseBuilder->toResponse();
    }

    protected function shouldContinue(Request $request): bool
    {
        return $this->responseBuilder->steps->count() < $request->maxSteps();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  ToolResult[]  $toolResults
     */
    protected function addStep(array $data, Request $request, array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: data_get($data, 'message.content') ?? '',
            finishReason: $this->mapFinishReason($data),
            toolCalls: $this->mapToolCalls(data_get($data, 'message.tool_calls', []) ?? []),
            toolResults: $toolResults,
            usage: new Usage(
                data_get($data, 'prompt_eval_count', 0),
                data_get($data, 'eval_count', 0),
            ),
            responseMeta: new ResponseMeta(
                id: '',
                model: $request->model(),
            ),
            messages: $request->messages(),
            additionalContent: [],
        ));
    }
}
