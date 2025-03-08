<?php

declare(strict_types=1);

namespace PrismPHP\Prism\Concerns;

use PrismPHP\Prism\Contracts\Schema;

trait HasSchema
{
    protected ?Schema $schema = null;

    public function withSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }
}
