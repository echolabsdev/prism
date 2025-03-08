<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Providers\Ollama\Handlers;

use Illuminate\Http\Client\PendingRequest;
use PrismPHP\Prism\Concerns\CallsTools;
use PrismPHP\Prism\Enums\FinishReason;
use PrismPHP\Prism\Exceptions\PrismException;
use PrismPHP\Prism\Providers\Ollama\Concerns\MapsFinishReason;
use PrismPHP\Prism\Providers\Ollama\Concerns\MapsToolCalls;
use PrismPHP\Prism\Providers\Ollama\Concerns\ValidatesResponse;
use PrismPHP\Prism\Providers\Ollama\Maps\MessageMap;
use PrismPHP\Prism\Providers\Ollama\Maps\ToolMap;
use PrismPHP\Prism\Text\Request;
use PrismPHP\Prism\Text\Response;
use PrismPHP\Prism\Text\ResponseBuilder;
use PrismPHP\Prism\Text\Step;
use PrismPHP\Prism\ValueObjects\Messages\AssistantMessage;
use PrismPHP\Prism\ValueObjects\Messages\ToolResultMessage;
use PrismPHP\Prism\ValueObjects\Meta;
use PrismPHP\Prism\ValueObjects\ToolResult;
use PrismPHP\Prism\ValueObjects\Usage;
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
        if (count($request->systemPrompts()) > 1) {
            throw new PrismException('Ollama does not support multiple system prompts using withSystemPrompt / withSystemPrompts. However, you can provide additional system prompts by including SystemMessages in with withMessages.');
        }

        try {
            $response = $this
                ->client
                ->post('api/chat', [
                    'model' => $request->model(),
                    'system' => data_get($request->systemPrompts(), '0.content', ''),
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
            meta: new Meta(
                id: '',
                model: $request->model(),
            ),
            messages: $request->messages(),
            additionalContent: [],
            systemPrompts: $request->systemPrompts(),
        ));
    }
}
