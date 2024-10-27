<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Requests\TextRequest;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Contracts\View\View;

trait BuildsTextRequests
{
    /** @var array<string, mixed> */
    protected array $clientOptions = [];

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

    /**
     * @param  array<string, mixed>  $options
     */
    public function withClientOptions(array $options): self
    {
        $this->clientOptions = $options;

        return $this;
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
}
