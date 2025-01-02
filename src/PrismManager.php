<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Enums\Provider as ProviderEnum;
use EchoLabs\Prism\Providers\Anthropic\Anthropic;
use EchoLabs\Prism\Providers\Gemini\Gemini;
use EchoLabs\Prism\Providers\Groq\Groq;
use EchoLabs\Prism\Providers\Mistral\Mistral;
use EchoLabs\Prism\Providers\Ollama\Ollama;
use EchoLabs\Prism\Providers\OpenAI\OpenAI;
use EchoLabs\Prism\Providers\XAI\XAI;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use RuntimeException;

class PrismManager
{
    /** @var array<string, Closure> */
    protected array $customCreators = [];

    public function __construct(
        protected Application $app
    ) {}

    /**
     * @param  array<string, mixed>  $providerConfig
     *
     * @throws InvalidArgumentException
     */
    public function resolve(ProviderEnum|string $name, array $providerConfig = []): Provider
    {
        $name = $this->resolveName($name);

        $config = array_merge($this->getConfig($name), $providerConfig);

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name, $config);
        }

        $factory = sprintf('create%sProvider', ucfirst($name));

        if (method_exists($this, $factory)) {
            return $this->{$factory}($config);
        }

        throw new InvalidArgumentException("Provider [{$name}] is not supported.");
    }

    /**
     * @throws RuntimeException
     */
    public function extend(string $provider, Closure $callback): self
    {
        if (($callback = $callback->bindTo($this, $this)) instanceof \Closure) {
            $this->customCreators[$provider] = $callback;

            return $this;
        }

        throw new RuntimeException(
            sprintf('Couldn\'t bind %s', $provider)
        );
    }

    protected function resolveName(ProviderEnum|string $name): string
    {
        if ($name instanceof ProviderEnum) {
            $name = $name->value;
        }

        return strtolower($name);
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createOpenaiProvider(array $config): OpenAI
    {
        return new OpenAI(
            apiKey: $config['api_key'] ?? '',
            url: $config['url'],
            organization: $config['organization'] ?? null,
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createOllamaProvider(array $config): Ollama
    {
        return new Ollama(
            apiKey: $config['api_key'] ?? '',
            url: $config['url'],
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createMistralProvider(array $config): Mistral
    {
        return new Mistral(
            apiKey: $config['api_key'],
            url: $config['url'],
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createAnthropicProvider(array $config): Anthropic
    {
        return new Anthropic(
            $config['api_key'],
            $config['version'],
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function callCustomCreator(string $provider, array $config): Provider
    {
        return $this->customCreators[$provider]($this->app, $config);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConfig(string $name): array
    {
        return config("prism.providers.{$name}", []);
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createGroqProvider(array $config): Groq
    {
        return new Groq(
            url: $config['url'],
            apiKey: $config['api_key'],
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createXaiProvider(array $config): XAI
    {
        return new XAI(
            url: $config['url'],
            apiKey: $config['api_key'],
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createGeminiProvider(array $config): Gemini
    {
        return new Gemini(
            url: $config['url'],
            apiKey: $config['api_key'],
        );
    }
}
