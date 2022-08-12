<?php

declare(strict_types=1);

namespace PhpTypes\Types\Dto;

use PhpTypes\Ast\Node\Dto\CallableParameter as CallableParameterNode;
use PhpTypes\Types\AbstractType;

final class CallableParameter
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

    public function toNode(): CallableParameterNode
    {
        return $this->optional
            ? CallableParameterNode::optional($this->type->toNode())
            : CallableParameterNode::required($this->type->toNode());
    }
}
