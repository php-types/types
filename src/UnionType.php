<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\UnionNode;

final class UnionType extends AbstractType
{
    public function __construct(public readonly AbstractType $left, public readonly AbstractType $right)
    {
    }

    public function toNode(): NodeInterface
    {
        return new UnionNode($this->left->toNode(), $this->right->toNode());
    }
}
