<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use Closure;
use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\Enums\Provider;
use EchoLabs\Prism\Enums\ToolChoice;
use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\Text\Request;
use EchoLabs\Prism\Tool;
use EchoLabs\Prism\ValueObjects\Messages\UserMessage;
use Illuminate\Contracts\View\View;

trait BuildsTextRequests
{
    /** @var array<string, mixed> */
    protected array $clientOptions = [];

    /** @var array{0: array<int, int>|int, 1?: Closure|int, 2?: ?callable, 3?: bool} */
    protected array $clientRetry = [0];

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

    protected string|ToolChoice|null $toolChoice = null;

    protected string $provider;

    protected string $model;

    /** @var array<string, mixed> */
    protected array $providerConfig = [];

    /** @var array<string, array<string, mixed>> */
    protected $providerMeta = [];

    public function using(string|Provider $provider, string $model): self
    {
        $this->provider = is_string($provider) ? $provider : $provider->value;
        $this->model = $model;

        return $this;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function withPrompt(string|View $prompt): self
    {
        if ($this->messages) {
            throw PrismException::promptOrMessages();
        }

        $this->prompt = is_string($prompt) ? $prompt : $prompt->render();

        $this->messages[] = new UserMessage($this->prompt);

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

        $this->messages = $messages;

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

    /**
     * @param  array<int>|int  $times
     */
    public function withClientRetry(
        array|int $times,
        Closure|int $sleepMilliseconds = 0,
        ?callable $when = null,
        bool $throw = true
    ): self {
        $this->clientRetry = [$times, $sleepMilliseconds, $when, $throw];

        return $this;
    }

    public function withToolChoice(string|ToolChoice|Tool $toolChoice): self
    {
        $this->toolChoice = $toolChoice instanceof Tool
            ? $toolChoice->name()
            : $toolChoice;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withProviderMeta(Provider $provider, array $meta): self
    {
        $this->providerMeta[$provider->value] = $meta;

        return $this;
    }

    protected function textRequest(): Request
    {
        return new Request(
            model: $this->model,
            systemPrompt: $this->systemPrompt,
            prompt: $this->prompt,
            messages: $this->messages,
            temperature: $this->temperature,
            maxTokens: $this->maxTokens,
            topP: $this->topP,
            tools: $this->tools,
            clientOptions: $this->clientOptions,
            clientRetry: $this->clientRetry,
            toolChoice: $this->toolChoice,
            providerMeta: $this->providerMeta,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function usingProviderConfig(array $config): self
    {
        $this->providerConfig = $config;

        return $this;
    }
}
