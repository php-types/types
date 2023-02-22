<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class ClassLikeType extends AbstractType
{
    /**
     * @param non-empty-string $name
     * @param list<AbstractType> $typeParameters
     * @param list<ClassLikeType> $parents
     */
    public function __construct(
        public readonly string $name,
        public readonly array $typeParameters = [],
        public readonly array $parents = [],
    ) {
    }

    public function toNode(): NodeInterface
    {
        $typeParameters = [];
        foreach ($this->typeParameters as $typeParameter) {
            $typeParameters[] = $typeParameter->toNode();
        }
        return new IdentifierNode($this->name, $typeParameters);
    }
}
