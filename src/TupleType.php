<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\TupleNode;

final class TupleType extends AbstractType
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
}
