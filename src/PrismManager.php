<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\Anthropic\Anthropic;
use EchoLabs\Prism\Providers\Ollama\Ollama;
use EchoLabs\Prism\Providers\OpenAI\OpenAI;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use RuntimeException;

class PrismManager
{
    /** @var array<string, Closure> */
    protected $customCreators = [];

    public function __construct(
        protected Application $app
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(string $name): Provider
    {
        $name = $this->resolveName($name);

        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Provider [{$name}] config is not defined.");
        }

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($config);
        }

        $factory = sprintf('create%sProvider', ucfirst($name));

        if (method_exists($this, $factory)) {
            return $this->{$factory}($config);
        }

        throw new InvalidArgumentException("Provider [{$name}] is not supported.");
    }

    protected function resolveName(string $name): string
    {
        if (class_exists($name)) {
            $name = class_basename($name);
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
    protected function callCustomCreator(array $config): Provider
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * @throws RuntimeException
     */
    public function extend(string $driver, Closure $callback): self
    {
        if (($callback = $callback->bindTo($this, $this)) instanceof \Closure) {
            $this->customCreators[$driver] = $callback;

            return $this;
        }

        throw new RuntimeException(
            sprintf('Couldn\'t bind %s', $driver)
        );
    }

    /**
     * @return null|array<string, mixed>
     */
    protected function getConfig(string $name): ?array
    {
        if ($name !== '' && $name !== '0') {
            return config("prism.providers.{$name}");
        }

        return ['driver' => 'null'];
    }
}
