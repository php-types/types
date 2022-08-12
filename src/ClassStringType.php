<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class ClassStringType extends AbstractType
{
    public function __construct(public readonly AbstractType|null $class = null)
    {
    }

    public function toNode(): NodeInterface
    {
        return new IdentifierNode(
            'class-string',
            $this->class === null ? [] : [$this->class->toNode()]
        );
    }
}
