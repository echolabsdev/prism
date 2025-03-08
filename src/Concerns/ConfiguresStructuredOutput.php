<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

use PrismPHP\Prism\Enums\StructuredMode;

trait ConfiguresStructuredOutput
{
    protected StructuredMode $structuredMode = StructuredMode::Auto;

    protected function usingStructuredMode(StructuredMode $mode): self
    {
        $this->structuredMode = $mode;

        return $this;
    }
}
