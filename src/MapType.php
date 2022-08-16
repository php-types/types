<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Types\Conversion\ToIterableInterface;

use function in_array;

final class MapType extends AbstractType implements ToIterableInterface
{
    public function __construct(
        public readonly AbstractType $keyType,
        public readonly AbstractType $valueType,
        public readonly bool $nonEmpty = false,
    ) {
    }

    public static function nonEmpty(AbstractType $keyType, AbstractType $valueType): self
    {
        return new self($keyType, $valueType, true);
    }

    private static function isArrayKey(AbstractType $type): bool
    {
        if (!$type instanceof UnionType) {
            return false;
        }
        $types = [(string)$type->left, (string)$type->right];
        return in_array('string', $types, true) && in_array('int', $types, true);
    }

    public function toNode(): NodeInterface
    {
        $keyNode = self::isArrayKey($this->keyType)
            ? new IdentifierNode('array-key')
            : $this->keyType->toNode();
        return new IdentifierNode(
            $this->nonEmpty ? 'non-empty-array' : 'array',
            [$keyNode, $this->valueType->toNode()]
        );
    }

    public function toIterable(): IterableType
    {
        return new IterableType($this->keyType, $this->valueType);
    }
}
