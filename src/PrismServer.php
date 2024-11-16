<?php

declare(strict_types=1);

namespace EchoLabs\Prism;

use Closure;
use EchoLabs\Prism\Text\Generator;
use Illuminate\Support\Collection;

class PrismServer
{
    /**
     * @param  Collection<int, array{name: string, prism: Closure():Generator|callable():Generator}>  $prisms
     * */
    public function __construct(
        protected readonly Collection $prisms = new Collection,
    ) {}

    /** @param \Closure():Generator|callable():Generator $prism */
    public function register(string $name, Closure|callable $prism): self
    {
        $this->prisms->push(['name' => $name, 'prism' => $prism]);

        return $this;
    }

    /** @return Collection<int, array{name: string, prism: Closure():Generator|callable():Generator}> */
    public function prisms(): Collection
    {
        return $this->prisms;
    }
}
