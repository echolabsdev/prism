<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Message;

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
