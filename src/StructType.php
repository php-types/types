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

    public function toMap(): MapType
    {
        /** @var array{AbstractType, AbstractType}|null $types */
        $types = null;
        foreach ($this->members as $name => $member) {
            if ($types === null) {
                $types = [new StringLiteralType($name), $member->type];
                continue;
            }
            $types[0] = new UnionType($types[0], new StringLiteralType($name));
            $types[1] = new UnionType($types[1], $member->type);
        }
        return new MapType($types[0], $types[1], true);
    }
}
