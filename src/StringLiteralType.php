<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\StringLiteralNode;

final class StringLiteralType extends AbstractType
{
    public function __construct(public readonly string $value)
    {
    }

    public function toNode(): NodeInterface
    {
        return new StringLiteralNode($this->value);
    }
}
