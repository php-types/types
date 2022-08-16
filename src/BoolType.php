<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;

final class BoolType extends AbstractType
{
    public function __construct(public readonly ?bool $value = null)
    {
    }

    public function toNode(): NodeInterface
    {
        return new IdentifierNode(
            match ($this->value) {
                null => 'bool',
                true => 'true',
                false => 'false',
            }
        );
    }
}
