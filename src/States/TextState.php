<?php

declare(strict_types=1);

namespace EchoLabs\Prism\States;

use EchoLabs\Prism\Contracts\Message;
use EchoLabs\Prism\ValueObjects\TextStep;
use Illuminate\Support\Collection;

class TextState
{
    /**
     * @param  Collection<int, Message>  $messages
     * @param  Collection<int, TextStep>  $steps
     * @param  Collection<int, Message>  $responseMessages
     */
    public function __construct(
        protected Collection $messages = new Collection,
        protected Collection $steps = new Collection,
        protected Collection $responseMessages = new Collection,
    ) {}

    public function addMessage(Message $message): self
    {
        $this->messages->push($message);

        return $this;
    }

    /**
     * @param  array<int, Message>|Collection<int, Message>  $messages
     */
    public function setMessages(array|Collection $messages): self
    {
        $this->messages = new Collection($messages);

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function messages(): Collection
    {
        return $this->messages;
    }

    public function addStep(TextStep $step): self
    {
        $this->steps->push($step);

        return $this;
    }

    /**
     * @return Collection<int, TextStep>
     */
    public function steps(): Collection
    {
        return $this->steps;
    }

    public function addResponseMessage(Message $message): self
    {
        $this->messages->push($message);
        $this->responseMessages->push($message);

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function responseMessages(): Collection
    {
        return $this->responseMessages;
    }
}
