<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use Illuminate\Contracts\View\View;

trait HasPrompts
{
    protected ?string $prompt = null;

    protected ?string $systemPrompt = null;

    public function withPrompt(string|View $prompt): self
    {
        $this->prompt = is_string($prompt) ? $prompt : $prompt->render();

        return $this;
    }

    public function withSystemPrompt(string|View $message): self
    {
        $this->systemPrompt = is_string($message) ? $message : $message->render();

        return $this;
    }
}
