<?php

declare(strict_types=1);

namespace PhpTypes\Types\Dto;

use PhpTypes\Ast\Node\Dto\StructMember as StructMemberNode;
use PhpTypes\Types\AbstractType;

final class StructMember
{
    private function __construct(
        public readonly AbstractType $type,
        public readonly bool $optional,
    ) {
    }

    public static function required(AbstractType $type): self
    {
        return new self($type, false);
    }

    public static function optional(AbstractType $type): self
    {
        return new self($type, true);
    }

    public function toNode(): StructMemberNode
    {
        return $this->optional
            ? StructMemberNode::optional($this->type->toNode())
            : StructMemberNode::required($this->type->toNode());
    }
}
