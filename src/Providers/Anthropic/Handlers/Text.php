<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Anthropic\Handlers;

use EchoLabs\Prism\Concerns\CallsTools;
use EchoLabs\Prism\Contracts\PrismRequest;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Providers\Anthropic\Maps\FinishReasonMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\MessageMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolChoiceMap;
use EchoLabs\Prism\Providers\Anthropic\Maps\ToolMap;
use EchoLabs\Prism\Text\Request as TextRequest;
use EchoLabs\Prism\Text\Response;
use EchoLabs\Prism\Text\ResponseBuilder;
use EchoLabs\Prism\Text\Step;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Meta;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use EchoLabs\Prism\ValueObjects\Usage;
use Illuminate\Support\Collection;

/**
 * @template TRequest of TextRequest
 */
class Text extends AnthropicHandlerAbstract
{
    use CallsTools;

    /**
     * @var TextRequest
     */
    protected PrismRequest $request; // Redeclared for type hinting

    protected Response $tempResponse;

    protected ResponseBuilder $responseBuilder;

    public function __construct(mixed ...$args)
    {
        parent::__construct(...$args);

        $this->responseBuilder = new ResponseBuilder;
    }

    public function handle(): Response
    {
        $this->sendRequest();

        $this->prepareTempResponse();

        $responseMessage = new AssistantMessage(
            $this->tempResponse->text,
            $this->tempResponse->toolCalls,
            $this->tempResponse->additionalContent,
        );

        $this->responseBuilder->addResponseMessage($responseMessage);

        $this->request->addMessage($responseMessage);

        return match ($this->tempResponse->finishReason) {
            FinishReason::ToolCalls => $this->handleToolCalls(),
            FinishReason::Stop, FinishReason::Length => $this->handleStop(),
            default => throw new PrismException('Anthropic: unknown finish reason'),
        };
    }

    /**
     * @param  TextRequest  $request
     * @return array<string, mixed>
     */
    #[\Override]
    public static function buildHttpRequestPayload(PrismRequest $request): array
    {
        if (! $request->is(TextRequest::class)) {
            throw new \InvalidArgumentException('Request must be an instance of '.TextRequest::class);
        }

        return array_filter([
            'model' => $request->model(),
            'system' => MessageMap::mapSystemMessages($request->systemPrompts()),
            'messages' => MessageMap::map($request->messages(), $request->providerMeta(Provider::Anthropic)),
            'thinking' => $request->providerMeta(Provider::Anthropic, 'thinking.enabled') === true
                ? [
                    'type' => 'enabled',
                    'budget_tokens' => is_int($request->providerMeta(Provider::Anthropic, 'thinking.budgetTokens'))
                        ? $request->providerMeta(Provider::Anthropic, 'thinking.budgetTokens')
                        : config('prism.anthropic.default_thinking_budget', 1024),
                ]
                : null,
            'max_tokens' => $request->maxTokens(),
            'temperature' => $request->temperature(),
            'top_p' => $request->topP(),
            'tools' => ToolMap::map($request->tools()),
            'tool_choice' => ToolChoiceMap::map($request->toolChoice()),
        ]);
    }

    protected function handleToolCalls(): Response
    {
        $toolResults = $this->callTools($this->request->tools(), $this->tempResponse->toolCalls);
        $message = new ToolResultMessage($toolResults);

        $this->request->addMessage($message);

        $this->addStep($toolResults);

        if ($this->shouldContinue()) {
            return $this->handle();
        }

        return $this->responseBuilder->toResponse();
    }

    protected function handleStop(): Response
    {
        $this->addStep();

        return $this->responseBuilder->toResponse();
    }

    /**
     * @param  ToolResult[]  $toolResults
     */
    protected function addStep(array $toolResults = []): void
    {
        $this->responseBuilder->addStep(new Step(
            text: $this->tempResponse->text,
            finishReason: $this->tempResponse->finishReason,
            toolCalls: $this->tempResponse->toolCalls,
            toolResults: $toolResults,
            usage: $this->tempResponse->usage,
            meta: $this->tempResponse->meta,
            messages: $this->request->messages(),
            systemPrompts: $this->request->systemPrompts(),
            additionalContent: $this->tempResponse->additionalContent,
        ));
    }

    protected function shouldContinue(): bool
    {
        return $this->responseBuilder->steps->count() < $this->request->maxSteps();
    }

    protected function prepareTempResponse(): void
    {
        $data = $this->httpResponse->json();

        $this->tempResponse = new Response(
            steps: new Collection,
            responseMessages: new Collection,
            messages: new Collection,
            text: $this->extractText($data),
            finishReason: FinishReasonMap::map(data_get($data, 'stop_reason', '')),
            toolCalls: $this->extractToolCalls($data),
            toolResults: [],
            usage: new Usage(
                promptTokens: data_get($data, 'usage.input_tokens'),
                completionTokens: data_get($data, 'usage.output_tokens'),
                cacheWriteInputTokens: data_get($data, 'usage.cache_creation_input_tokens'),
                cacheReadInputTokens: data_get($data, 'usage.cache_read_input_tokens')
            ),
            meta: new Meta(
                id: data_get($data, 'id'),
                model: data_get($data, 'model'),
                rateLimits: $this->processRateLimits()
            ),
            additionalContent: array_filter([
                'messagePartsWithCitations' => $this->extractCitations($data),
                ...$this->extractThinking($data),
            ])
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return ToolCall[]
     */
    protected function extractToolCalls(array $data): array
    {
        $toolCalls = [];
        $contents = data_get($data, 'content', []);

        foreach ($contents as $content) {
            if (data_get($content, 'type') === 'tool_use') {
                $toolCalls[] = new ToolCall(
                    id: data_get($content, 'id'),
                    name: data_get($content, 'name'),
                    arguments: data_get($content, 'input')
                );
            }
        }

        return $toolCalls;
    }
}
