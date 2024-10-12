<?php

namespace EchoLabs\Prism;

use Illuminate\Support\ServiceProvider;

class PrismServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/prism.php' => config_path('prism.php'),
        ], 'prism-config');

        if (config('prism.prism_server.enabled')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/PrismServer.php');
        }
    }

    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/prism.php',
            'prism'
        );

        $this->app->singleton(
            'prism-server',
            fn (): PrismServer => new PrismServer
        );
    }
}
