<?php

declare(strict_types=1);

use EchoLabs\Prism\Prism;
use Illuminate\Support\Facades\App;

if (! function_exists('prism')) {

    /**
     * A fluent helper function to resolve prism from
     * the application container.
     */
    function prism(): Prism
    {
        return App::make(Prism::class);
    }
}
