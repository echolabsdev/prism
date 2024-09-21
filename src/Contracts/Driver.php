<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Contracts;

use EchoLabs\Prism\Drivers\DriverResponse;
use EchoLabs\Prism\Requests\TextRequest;

interface Driver
{
    public function usingModel(string $model): Driver;

    public function text(TextRequest $request): DriverResponse;
}
