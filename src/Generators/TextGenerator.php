<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Generators;

use EchoLabs\Prism\Concerns\HandlesToolCalls;
use EchoLabs\Prism\Concerns\HasClientOptions;
use EchoLabs\Prism\Concerns\HasProvider;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\FinishReason;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\PrismManager;
use EchoLabs\Prism\Providers\ProviderResponse;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\Responses\TextResponse;
use EchoLabs\Prism\States\TextState;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\AssistantMessage;
use EchoLabs\Prism\ValueObjects\Messages\ToolResultMessage;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use EchoLabs\Prism\ValueObjects\TextResult;
use EchoLabs\Prism\ValueObjects\ToolCall;
use EchoLabs\Prism\ValueObjects\ToolResult;
use Illuminate\Contracts\View\View;

class TextGenerator
{
    use HandlesToolCalls, HasClientOptions, HasProvider;

    protected ?string $prompt = null;

    protected ?string $systemPrompt = null;

    /** @var array<int, Message> */
    protected array $messages = [];

    protected ?int $maxTokens = null;

    protected int $maxSteps = 1;

    /** @var array<int, Tool> */
    protected array $tools = [];

    protected int|float|null $temperature = null;

    protected int|float|null $topP = null;

    protected TextState $state;

    public function __construct()
    {
        $this->state = new TextState;
    }

    public function __invoke(): TextResponse
    {
        return $this->generate();
    }

    public function withPrompt(string|View $prompt): self
    {
        if ($this->messages) {
            throw PrismException::promptOrMessages();
        }

        $this->prompt = is_string($prompt) ? $prompt : $prompt->render();

        $this->state->addMessage(new UserMessage($this->prompt));

        return $this;
    }

    public function withSystemPrompt(string|View $message): self
    {
        $this->systemPrompt = is_string($message) ? $message : $message->render();

        return $this;
    }

    /**
     * @param  array<int, Tool>  $tools
     */
    public function withTools(array $tools): self
    {
        $this->tools = $tools;

        return $this;
    }

    /**
     * @param  array<int, Message>  $messages
     */
    public function withMessages(array $messages): self
    {
        if ($this->prompt) {
            throw PrismException::promptOrMessages();
        }

        $this->state->setMessages($messages);

        return $this;
    }

    public function withMaxTokens(?int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function usingTemperature(int|float|null $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function usingTopP(int|float $topP): self
    {
        $this->topP = $topP;

        return $this;
    }

    public function withMaxSteps(int $steps): self
    {
        $this->maxSteps = $steps;

        return $this;
    }

    public function generate(): TextResponse
    {
        $response = $this->sendProviderRequest();

        if ($response->finishReason === FinishReason::ToolCalls) {
            $toolResults = $this->handleToolCalls($response);
        }

        $this->state->addStep(
            $this->resultFromResponse($response, $toolResults ?? [])
        );

        if ($this->shouldContinue($response)) {
            return $this();
        }

        return new TextResponse($this->state);
    }

    /**
     * @param  array<int, ToolResult>  $toolResults
     */
    protected function resultFromResponse(ProviderResponse $response, array $toolResults): TextResult
    {
        return new TextResult(
            text: $response->text,
            finishReason: $response->finishReason,
            toolCalls: $response->toolCalls,
            toolResults: $toolResults,
            usage: $response->usage,
            response: $response->response,
            messages: $this->state->messages()->toArray(),
        );
    }

    protected function sendProviderRequest(): ProviderResponse
    {
        $response = resolve(PrismManager::class)
            ->resolve($this->provider)
            ->text($this->textRequest());

        $this->state->addResponseMessage(
            new AssistantMessage(
                $response->text,
                $response->toolCalls
            )
        );

        return $response;
    }

    protected function textRequest(): TextRequest
    {
        return new TextRequest(
            model: $this->model,
            systemPrompt: $this->systemPrompt,
            messages: $this->state->messages()->toArray(),
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
        );
    }

    /**
     * @return array<int, ToolResult>
     */
    protected function handleToolCalls(ProviderResponse $response): array
    {
        $toolResults = array_map(function (ToolCall $toolCall): ToolResult {
            $result = $this->handleToolCall($this->tools, $toolCall);

            return new ToolResult(
                toolCallId: $toolCall->id,
                toolName: $toolCall->name,
                args: $toolCall->arguments(),
                result: $result,
            );
        }, $response->toolCalls);

        $this->state->addResponseMessage(new ToolResultMessage($toolResults));

        return $toolResults;
    }

    protected function shouldContinue(ProviderResponse $response): bool
    {
        return $this->state->steps()->count() < $this->maxSteps
            && $response->finishReason !== FinishReason::Stop;
    }
}
