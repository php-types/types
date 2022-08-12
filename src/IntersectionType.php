<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IntersectionNode;
use PhpTypes\Ast\Node\NodeInterface;

final class IntersectionType extends AbstractType
{
    public function __construct(public readonly AbstractType $left, public readonly AbstractType $right)
    {
    }

    public function toNode(): NodeInterface
    {
        return new IntersectionNode($this->left->toNode(), $this->right->toNode());
    }
}
