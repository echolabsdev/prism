<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

use PrismPHP\Prism\Contracts\Message;

trait HasMessages
{
    /** @var array<int, Message> */
    protected array $messages = [];

    /**
     * @param  array<int, Message>  $messages
     */
    public function withMessages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }
}
