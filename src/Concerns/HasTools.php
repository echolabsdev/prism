<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

use PrismPHP\Prism\Tool;

trait HasTools
{
    /** @var array<int, Tool> */
    protected array $tools = [];

    /**
     * @param  array<int, Tool>  $tools
     */
    public function withTools(array $tools): self
    {
        $this->tools = $tools;

        return $this;
    }
}
