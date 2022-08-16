<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\TupleNode;
use PhpTypes\Types\Conversion\ToIterableInterface;
use PhpTypes\Types\Conversion\ToMapInterface;

final class TupleType extends AbstractType implements ToIterableInterface, ToMapInterface
{
    /**
     * @param list<AbstractType> $elements
     */
    public function __construct(public readonly array $elements)
    {
    }

    public function toNode(): NodeInterface
    {
        $elementNodes = [];
        foreach ($this->elements as $element) {
            $elementNodes[] = $element->toNode();
        }
        return new TupleNode($elementNodes);
    }

    public function toMap(): MapType
    {
        return new MapType(new IntType(), $this->valueType());
    }

    public function toIterable(): IterableType
    {
        return new IterableType(new IntType(), $this->valueType());
    }

    public function valueType(): AbstractType
    {
        if ($this->elements === []) {
            return new MixedType();
        }
        $valueType = null;
        foreach ($this->elements as $element) {
            if ($valueType === null) {
                $valueType = $element;
                continue;
            }
            if (Compatibility::check($valueType, $element)) {
                continue;
            }
            $valueType = new UnionType($valueType, $element);
        }
        return $valueType;
    }
}
