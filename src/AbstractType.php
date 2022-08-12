<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use Stringable;

abstract class AbstractType implements Stringable
{
    public function __toString(): string
    {
        return (string)$this->toNode();
    }

    abstract public function toNode(): NodeInterface;
}
