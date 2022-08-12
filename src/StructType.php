<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\StructNode;
use PhpTypes\Types\Dto\StructMember;

final class StructType extends AbstractType
{
    /**
     * @param array<non-empty-string, StructMember> $members
     */
    public function __construct(public readonly array $members)
    {
    }

    public function toNode(): NodeInterface
    {
        $members = [];
        foreach ($this->members as $name => $member) {
            $members[$name] = $member->toNode();
        }
        return new StructNode($members);
    }
}
