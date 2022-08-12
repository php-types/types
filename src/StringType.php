<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class StringType extends AbstractType
{
    public function __construct(public readonly bool $nonEmpty = false, public readonly bool $numeric = false)
    {
    }

    public static function nonEmpty(): AbstractType
    {
        return new StringType(true);
    }

    public static function numeric(): AbstractType
    {
        return new StringType(true, true);
    }

    public function __toString(): string
    {
        if ($this->numeric) {
            return 'numeric-string';
        }
        return $this->nonEmpty ? 'non-empty-string' : 'string';
    }

    public function toNode(): NodeInterface
    {
        if ($this->numeric) {
            return new IdentifierNode('numeric-string');
        }
        return new IdentifierNode($this->nonEmpty ? 'non-empty-string' : 'string');
    }
}
