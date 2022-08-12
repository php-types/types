<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IntLiteralNode;
use PhpTypes\Ast\Node\NodeInterface;

final class IntLiteralType extends AbstractType
{
    public function __construct(public readonly int $value)
    {
    }

    public function toNode(): NodeInterface
    {
        return new IntLiteralNode($this->value);
    }
}
