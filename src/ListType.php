<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class ListType extends AbstractType
{
    public function __construct(public readonly AbstractType $type, public readonly bool $nonEmpty = false)
    {
    }

    public function nonEmpty(AbstractType $type): AbstractType
    {
        return new ListType($type, true);
    }

    public function toNode(): NodeInterface
    {
        return new IdentifierNode($this->nonEmpty ? 'non-empty-list' : 'list', [$this->type->toNode()]);
    }

    public function toMap(): MapType
    {
        return new MapType(new IntType(), $this->type, $this->nonEmpty);
    }
}
