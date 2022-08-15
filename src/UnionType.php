<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\UnionNode;
use PhpTypes\Types\Conversion\ToIterableInterface;
use PhpTypes\Types\Conversion\ToMapInterface;

final class UnionType extends AbstractType implements ToIterableInterface, ToMapInterface
{
    public function __construct(public readonly AbstractType $left, public readonly AbstractType $right)
    {
    }

    public function toNode(): NodeInterface
    {
        return new UnionNode($this->left->toNode(), $this->right->toNode());
    }

    /**
     * @return list<AbstractType>
     */
    public function flatten(): array
    {
        return array_merge(
            $this->left instanceof UnionType ? $this->left->flatten() : [$this->left],
            $this->right instanceof UnionType ? $this->right->flatten() : [$this->right],
        );
    }

    public function toIterable(): IterableType|NeverType
    {
        $left = $this->left instanceof ToIterableInterface ? $this->left->toIterable() : $this->left;
        if (!$left instanceof IterableType) {
            return new NeverType();
        }
        $right = $this->right instanceof ToIterableInterface ? $this->right->toIterable() : $this->right;
        if (!$right instanceof IterableType) {
            return new NeverType();
        }
        return new IterableType(
            new UnionType($left->keyType, $right->keyType),
            new UnionType($left->valueType, $right->valueType),
        );
    }

    public function toMap(): MapType|NeverType
    {
        $left = $this->left instanceof ToMapInterface ? $this->left->toMap() : $this->left;
        if (!$left instanceof MapType) {
            return new NeverType();
        }
        $right = $this->right instanceof ToMapInterface ? $this->right->toMap() : $this->right;
        if (!$right instanceof MapType) {
            return new NeverType();
        }
        return new MapType(
            new UnionType($left->keyType, $right->keyType),
            new UnionType($left->valueType, $right->valueType),
        );
    }

    public function toList(): ListType|NeverType
    {
        $left = $this->left instanceof self ? $this->left->toList() : $this->left;
        if (!$left instanceof ListType) {
            return new NeverType();
        }
        $right = $this->right instanceof self ? $this->right->toList() : $this->right;
        if (!$right instanceof ListType) {
            return new NeverType();
        }
        return new ListType(
            new UnionType($left->type, $right->type),
            $left->nonEmpty || $right->nonEmpty,
        );
    }
}
