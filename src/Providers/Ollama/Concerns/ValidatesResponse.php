<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Providers\Ollama\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;
use Illuminate\Support\Fluent;

trait ValidatesResponse
{
    /**
     * @param  Fluent<string, mixed>  $data
     */
    protected function validateResponse(Fluent $data): void
    {
        if ($data->has('error')) {
            throw PrismException::providerResponseError(sprintf(
                'Ollama Error: %s',
                $data->get('error', 'unknown')
            ));
        }
    }
}
