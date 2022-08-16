<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\IntLiteralNode;
use PhpTypes\Ast\Node\NodeInterface;

final class IntType extends AbstractType
{
    public function __construct(public readonly int|null $min = null, public readonly int|null $max = null)
    {
    }

    public static function min(int $min): self
    {
        return new self($min, null);
    }

    public static function max(int $max): self
    {
        return new self(null, $max);
    }

    public function toNode(): NodeInterface
    {
        if ($this->min === null && $this->max === null) {
            return new IdentifierNode('int');
        }
        if ($this->min === 1 && $this->max === null) {
            return new IdentifierNode('positive-int');
        }
        if ($this->min === null && $this->max === -1) {
            return new IdentifierNode('negative-int');
        }
        return new IdentifierNode('int', [
            $this->min === null ? new IdentifierNode('min') : new IntLiteralNode($this->min),
            $this->max === null ? new IdentifierNode('max') : new IntLiteralNode($this->max),
        ]);
    }
}
