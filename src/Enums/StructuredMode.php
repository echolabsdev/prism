<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Enums;

enum StructuredMode
{
    case Auto;
    case Json;
    case Structured;
    case Unsupported;
}
