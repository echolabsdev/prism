<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Enums\StructuredMode;

trait ConfiguresStructuredOutput
{
    protected StructuredMode $structuredMode = StructuredMode::Auto;

    protected function usingStructuredMode(StructuredMode $mode): self
    {
        $this->structuredMode = $mode;

        return $this;
    }
}
