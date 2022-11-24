<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Types\Conversion\ToIterableInterface;
use PhpTypes\Types\Conversion\ToMapInterface;

final class ListType extends AbstractType implements ToIterableInterface, ToMapInterface
{
    public function __construct(public readonly AbstractType $type, public readonly bool $nonEmpty = false)
    {
    }

    public function toNode(): NodeInterface
    {
        return new IdentifierNode($this->nonEmpty ? 'non-empty-list' : 'list', [$this->type->toNode()]);
    }

    public function toMap(): MapType
    {
        return new MapType(new IntType(), $this->type, $this->nonEmpty);
    }

    public function toIterable(): IterableType
    {
        return new IterableType(new IntType(), $this->type);
    }
}
