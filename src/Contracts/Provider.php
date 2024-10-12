<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Providers\DriverResponse;
use EchoLabs\Prism\Requests\TextRequest;

interface Provider
{
    public function usingModel(string $model): Provider;

    public function text(TextRequest $request): DriverResponse;

    public static function make(string $model): Provider;
}
