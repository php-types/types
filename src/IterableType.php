<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class IterableType extends AbstractType
{
    public function __construct(
        public readonly AbstractType $keyType,
        public readonly AbstractType $valueType,
    ) {
    }

    public function toNode(): NodeInterface
    {
        return new IdentifierNode('iterable', [
            $this->keyType->toNode(),
            $this->valueType->toNode(),
        ]);
    }
}
