<?php

declare(strict_types=1);

namespace EchoLabs\Prism\Concerns;

use EchoLabs\Prism\Contracts\Schema;

trait HasSchema
{
    protected ?Schema $schema = null;

    public function withSchema(Schema $schema): self
    {
        $this->schema = $schema;

        return $this;
    }
}
