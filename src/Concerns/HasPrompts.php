<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Exceptions\PrismException;
use EchoLabs\Prism\ValueObjects\Messages\SystemMessage;
use Illuminate\Contracts\View\View;

trait HasPrompts
{
    protected ?string $prompt = null;

    /**
     * @var SystemMessage[]
     */
    protected array $systemPrompts = [];

    public function withPrompt(string|View $prompt): self
    {
        $this->prompt = is_string($prompt) ? $prompt : $prompt->render();

        return $this;
    }

    public function withSystemPrompt(string|View|SystemMessage $message): self
    {
        if ($message instanceof SystemMessage) {
            $this->systemPrompts[] = $message;

            return $this;
        }

        $this->systemPrompts[] = new SystemMessage(is_string($message) ? $message : $message->render());

        return $this;
    }

    /**
     * @param  SystemMessage[]  $messages
     */
    public function withSystemPrompts(array $messages): self
    {
        if (count($this->systemPrompts) > 0) {
            throw new PrismException('System prompts have already been set. Remove previous calls to withSystemPrompt or withSystemPrompts.');
        }

        $this->systemPrompts = $messages;

        return $this;
    }
}
