<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\StructNode;
use PhpTypes\Types\Conversion\ToIterableInterface;
use PhpTypes\Types\Conversion\ToMapInterface;
use PhpTypes\Types\Dto\StructMember;

final class StructType extends AbstractType implements ToIterableInterface, ToMapInterface
{
    /**
     * @param non-empty-array<non-empty-string, StructMember> $members
     */
    public function __construct(public readonly array $members)
    {
    }

    /**
     * @param non-empty-array<array-key, self> $structs
     */
    public static function merge(iterable $structs): self
    {
        $members = [];
        foreach ($structs as $struct) {
            foreach ($struct->members as $name => $member) {
                if (!isset($members[$name])) {
                    $members[$name] = $member;
                    continue;
                }
                $members[$name] = $members[$name]->intersect($member);
            }
        }
        return new self($members);
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
        $types = $this->keyAndValueType();
        return MapType::nonEmpty($types[0], $types[1]);
    }

    public function toIterable(): IterableType
    {
        $types = $this->keyAndValueType();
        return new IterableType($types[0], $types[1]);
    }

    /**
     * @return array{AbstractType, AbstractType}
     */
    private function keyAndValueType(): array
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
        return $types;
    }
}
