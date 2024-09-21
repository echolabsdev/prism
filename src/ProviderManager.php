<?php

declare(strict_types=1);

namespace EchoLabs\Sparkle;

use Closure;
use EchoLabs\Prism\Contracts\Provider;
use EchoLabs\Prism\Providers\Anthropic\Anthropic;
use EchoLabs\Prism\Providers\OpenAI\OpenAI;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ProviderManager
{
    protected $customCreators = [];

    public function __construct(
        protected Application $app
    ) {}

    #[\Override]
    public function getDefaultDriver()
    {
        return 'anthropic';
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(string $name): Provider
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Provider [{$name}] is not defined.");
        }

        $config = Arr::add($config, 'store', $name);

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst((string) $config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        }

        throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createOpenaiDriver(array $config): OpenAI
    {
        return new OpenAI(
            $config['api_key'],
            $config['url'],
        );
    }

    /**
     * @param  array<string, string>  $config
     */
    protected function createAnthropicDriver(array $config): Anthropic
    {
        return new Anthropic(
            $config['api_key'],
            $config['api_version'],
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string  $driver
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * @return null|array<string, mixed>
     */
    protected function getConfig(string $name): ?array
    {
        if ($name !== '' && $name !== '0') {
            return $this->app['config']["prism.providers.{$name}"];
        }

        return ['driver' => 'null'];
    }
}
