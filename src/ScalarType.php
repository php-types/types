<?php

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class ScalarType extends AbstractType
{
    public function toNode(): NodeInterface
    {
        return new IdentifierNode('scalar');
    }
}
