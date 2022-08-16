<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\CallableNode;
use PhpTypes\Ast\Node\Dto\StructMember as StructMemberNode;
use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\IntersectionNode;
use PhpTypes\Ast\Node\IntLiteralNode;
use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Ast\Node\StringLiteralNode;
use PhpTypes\Ast\Node\StructNode;
use PhpTypes\Ast\Node\TupleNode;
use PhpTypes\Ast\Node\UnionNode;
use PhpTypes\Ast\Parser;
use PhpTypes\Types\Dto\CallableParameter;
use PhpTypes\Types\Dto\StructMember;
use RuntimeException;

use function count;

final class Type
{
    private function __construct()
    {
    }

    public static function fromString(string $typeString, Scope|null $scope = null): AbstractType
    {
        $node = Parser::parse($typeString);
        return self::fromNode($node, $scope ?? Scope::global());
    }

    private static function fromNode(NodeInterface $node, Scope $scope): AbstractType
    {
        return match (true) {
            $node instanceof CallableNode => self::fromCallable($node, $scope),
            $node instanceof IdentifierNode => self::fromIdentifier($node, $scope),
            $node instanceof IntersectionNode => self::fromIntersection($node, $scope),
            $node instanceof IntLiteralNode => new IntLiteralType($node->value),
            $node instanceof StringLiteralNode => new StringLiteralType($node->value),
            $node instanceof StructNode => self::fromStruct($node->members, $scope),
            $node instanceof TupleNode => self::fromTuple($node, $scope),
            $node instanceof UnionNode => self::fromUnion($node, $scope),
            default => throw new RuntimeException(
                sprintf('Unsupported node type: %s (%s)', get_class($node), $node)
            ),
        };
    }

    private static function fromIdentifier(IdentifierNode $node, Scope $scope): AbstractType
    {
        if ($node->name === 'int') {
            return self::fromInt($node);
        }
        $typeParameters = [];
        foreach ($node->typeParameters as $typeParameter) {
            $typeParameters[] = self::fromNode($typeParameter, $scope);
        }
        return $scope->getType($node->name, $typeParameters);
    }

    private static function fromUnion(UnionNode $node, Scope $scope): AbstractType
    {
        $left = self::fromNode($node->left, $scope);
        $right = self::fromNode($node->right, $scope);
        if (Compatibility::check($left, $right)) {
            return $left;
        }
        if (Compatibility::check($right, $left)) {
            return $right;
        }
        if ($left instanceof BoolType && $right instanceof BoolType) {
            if (
                ($left->value === true && $right->value === false)
                || ($left->value === false && $right->value === true)
            ) {
                return new BoolType();
            }
        }
        return new UnionType($left, $right);
    }

    private static function fromTuple(TupleNode $node, Scope $scope): TupleType
    {
        $elements = [];
        foreach ($node->elements as $element) {
            $elements[] = self::fromNode($element, $scope);
        }
        return new TupleType($elements);
    }

    /**
     * @param array<non-empty-string, StructMemberNode> $members
     */
    private static function fromStruct(array $members, Scope $scope): StructType
    {
        $typeMembers = [];
        foreach ($members as $name => $member) {
            $typeMembers[$name] = $member->optional
                ? StructMember::optional(self::fromNode($member->type, $scope))
                : StructMember::required(self::fromNode($member->type, $scope));
        }
        return new StructType($typeMembers);
    }

    private static function fromCallable(CallableNode $node, Scope $scope): CallableType
    {
        $params = [];
        foreach ($node->parameterTypes as $param) {
            $params[] = $param->optional
                ? CallableParameter::optional(self::fromNode($param->type, $scope))
                : CallableParameter::required(self::fromNode($param->type, $scope));
        }
        return new CallableType(
            self::fromNode($node->returnType, $scope),
            $params,
        );
    }

    private static function fromIntersection(IntersectionNode $node, Scope $scope): AbstractType
    {
        return new IntersectionType(
            self::fromNode($node->left, $scope),
            self::fromNode($node->right, $scope),
        );
    }

    private static function fromInt(IdentifierNode $node): IntType
    {
        $numberOfParams = count($node->typeParameters);
        if ($numberOfParams === 0) {
            return new IntType();
        }
        if ($numberOfParams !== 2) {
            throw new RuntimeException('Invalid number of type parameters');
        }
        $min = (static function () use ($node) {
            if ($node->typeParameters[0] instanceof IdentifierNode && $node->typeParameters[0]->name === 'min') {
                return null;
            }
            if ($node->typeParameters[0] instanceof IntLiteralNode) {
                return $node->typeParameters[0]->value;
            }
            throw new RuntimeException('Invalid int type');
        })();
        $max = (static function () use ($node) {
            if ($node->typeParameters[1] instanceof IdentifierNode && $node->typeParameters[1]->name === 'max') {
                return null;
            }
            if ($node->typeParameters[1] instanceof IntLiteralNode) {
                return $node->typeParameters[1]->value;
            }
            throw new RuntimeException('Invalid int type');
        })();
        return new IntType($min, $max);
    }
}
