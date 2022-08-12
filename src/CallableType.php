<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\CallableNode;
use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Types\Dto\CallableParameter;

final class CallableType extends AbstractType
{
    /**
     * @param list<CallableParameter> $parameters
     */
    public function __construct(public readonly AbstractType $returnType, public readonly array $parameters)
    {
    }

    public function toNode(): NodeInterface
    {
        $parameters = [];
        foreach ($this->parameters as $parameter) {
            $parameters[] = $parameter->toNode();
        }
        return new CallableNode($this->returnType->toNode(), $parameters);
    }
}
