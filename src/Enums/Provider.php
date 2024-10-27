<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Enums;

enum Provider: string
{
    case Anthropic = 'anthropic';
    case Ollama = 'ollama';
    case OpenAI = 'openai';
    case Mistral = 'mistral';
    case Groq = 'groq';
}
