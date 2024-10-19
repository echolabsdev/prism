<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

trait HasClientOptions
{
    /** @var array<string, mixed> */
    protected array $clientOptions = [];

    /**
     * @param  array<string, mixed>  $options
     */
    public function withClientOptions(array $options): self
    {
        $this->clientOptions = $options;

        return $this;
    }
}
