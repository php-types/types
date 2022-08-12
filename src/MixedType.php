<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class MixedType extends AbstractType
{
    public function toNode(): NodeInterface
    {
        return new IdentifierNode('mixed');
    }
}
