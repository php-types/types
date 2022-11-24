<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use LogicException;
use PhpTypes\Types\Conversion\ToIterableInterface;
use PhpTypes\Types\Conversion\ToMapInterface;

use function array_key_exists;
use function count;
use function get_class;
use function is_numeric;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class Compatibility
{
    private function __construct()
    {
    }

    public static function check(AbstractType $super, AbstractType $sub): bool
    {
        if ($sub instanceof UnionType) {
            return self::checkSubUnion($super, $sub);
        }
        if ($sub instanceof IntersectionType) {
            return self::checkSubIntersection($super, $sub);
        }
        return match (true) {
            $super instanceof BoolType => self::checkBool($super, $sub),
            $super instanceof CallableType => self::checkCallable($super, $sub),
            $super instanceof ClassLikeType => self::checkClassLike($super, $sub),
            $super instanceof ClassStringType => self::checkClassString($super, $sub),
            $super instanceof FloatType => self::checkFloat($sub),
            $super instanceof IntLiteralType => self::checkIntLiteral($super, $sub),
            $super instanceof IntersectionType => self::checkIntersection($super, $sub),
            $super instanceof IntType => self::checkInt($super, $sub),
            $super instanceof IterableType => self::checkIterable($super, $sub),
            $super instanceof ListType => self::checkList($super, $sub),
            $super instanceof MapType => self::checkMap($super, $sub),
            $super instanceof MixedType => true,
            $super instanceof NeverType => false,
            $super instanceof NullType => $sub instanceof NullType,
            $super instanceof ScalarType => self::checkScalar($sub),
            $super instanceof StringLiteralType => self::checkStringLiteral($super, $sub),
            $super instanceof StringType => self::checkString($super, $sub),
            $super instanceof StructType => self::checkStruct($super, $sub),
            $super instanceof TupleType => self::checkTuple($super, $sub),
            $super instanceof UnionType => self::checkUnion($super, $sub),
            default => throw new LogicException(sprintf('Unsupported type "%s"', get_class($super))),
        };
    }

    private static function checkClassString(ClassStringType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof ClassStringType) {
            return false;
        }
        if ($super->class === null) {
            return true;
        }
        if ($sub->class === null) {
            return false;
        }
        return self::check($super->class, $sub->class);
    }

    private static function checkClassLike(ClassLikeType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof ClassLikeType) {
            return false;
        }
        if ($super->name === $sub->name) {
            return true;
        }
        foreach ($sub->parents as $parent) {
            if (self::check($super, $parent)) {
                return true;
            }
        }
        return false;
    }

    private static function checkString(StringType $super, AbstractType $sub): bool
    {
        if ($sub instanceof StringLiteralType) {
            if ($super->numeric) {
                return is_numeric($sub->value);
            }
            if ($super->nonEmpty) {
                return $sub->value !== '';
            }
            return true;
        }
        if ($sub instanceof ClassStringType) {
            return !$super->numeric;
        }
        if (!$sub instanceof StringType) {
            return false;
        }
        if ($super->numeric) {
            return $sub->numeric;
        }
        if ($super->nonEmpty) {
            return $sub->nonEmpty;
        }
        return true;
    }

    private static function checkBool(BoolType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof BoolType) {
            return false;
        }
        if ($super->value === null) {
            return true;
        }
        return $super->value === $sub->value;
    }

    private static function checkInt(IntType $super, AbstractType $sub): bool
    {
        if ($sub instanceof IntLiteralType) {
            return ($super->min ?? PHP_INT_MIN) <= $sub->value && $sub->value <= ($super->max ?? PHP_INT_MAX);
        }
        if (!$sub instanceof IntType) {
            return false;
        }
        [$superMin, $superMax, $subMin, $subMax] = [
            $super->min ?? PHP_INT_MIN,
            $super->max ?? PHP_INT_MAX,
            $sub->min ?? PHP_INT_MIN,
            $sub->max ?? PHP_INT_MAX,
        ];
        return $superMin <= $subMin && $superMax >= $subMax;
    }

    private static function checkIntLiteral(IntLiteralType $super, AbstractType $sub): bool
    {
        if ($sub instanceof IntLiteralType) {
            return $super->value === $sub->value;
        }
        if ($sub instanceof IntType) {
            return $sub->min === $super->value && $sub->max === $super->value;
        }
        return false;
    }

    private static function checkFloat(AbstractType $sub): bool
    {
        return $sub instanceof FloatType
            || $sub instanceof IntLiteralType
            || $sub instanceof IntType;
    }

    private static function checkUnion(UnionType $super, AbstractType $sub): bool
    {
        return self::check($super->left, $sub) || self::check($super->right, $sub);
    }

    private static function checkScalar(AbstractType $sub): bool
    {
        return $sub instanceof ScalarType
            || $sub instanceof IntLiteralType
            || $sub instanceof IntType
            || $sub instanceof FloatType
            || $sub instanceof BoolType
            || $sub instanceof StringType
            || $sub instanceof StringLiteralType
            || $sub instanceof ClassStringType;
    }

    private static function checkList(ListType $super, AbstractType $sub): bool
    {
        if ($sub instanceof TupleType) {
            foreach ($sub->elements as $element) {
                if (self::check($super->type, $element)) {
                    continue;
                }
                return false;
            }
            return true;
        }
        if (!$sub instanceof ListType) {
            return false;
        }
        if ($super->nonEmpty && !$sub->nonEmpty) {
            return false;
        }
        return self::check($super->type, $sub->type);
    }

    private static function checkMap(MapType $super, AbstractType $sub): bool
    {
        if ($sub instanceof ToMapInterface) {
            $sub = $sub->toMap();
        }
        if (!$sub instanceof MapType) {
            return false;
        }
        if ($super->nonEmpty && !$sub->nonEmpty) {
            return false;
        }
        return self::check($super->keyType, $sub->keyType) && self::check($super->valueType, $sub->valueType);
    }

    private static function checkTuple(TupleType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof TupleType) {
            return false;
        }
        if (count($super->elements) > count($sub->elements)) {
            return false;
        }
        foreach ($super->elements as $i => $element) {
            if (self::check($element, $sub->elements[$i])) {
                continue;
            }
            return false;
        }
        return true;
    }

    private static function checkStruct(StructType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof StructType) {
            return false;
        }
        foreach ($super->members as $name => $member) {
            $subMember = array_key_exists($name, $sub->members) ? $sub->members[$name] : null;
            if ($subMember === null) {
                if ($member->optional) {
                    continue;
                }
                return false;
            }
            if (!self::check($member->type, $subMember->type)) {
                return false;
            }
            if ($subMember->optional && !$member->optional) {
                return false;
            }
        }
        return true;
    }

    private static function checkStringLiteral(StringLiteralType $super, AbstractType $sub): bool
    {
        return $sub instanceof StringLiteralType && $super->value === $sub->value;
    }

    private static function checkCallable(CallableType $super, AbstractType $sub): bool
    {
        if (!$sub instanceof CallableType) {
            return false;
        }
        if (!$super->returnType instanceof VoidType && !self::check($super->returnType, $sub->returnType)) {
            return false;
        }
        if (count($sub->parameters) > count($super->parameters)) {
            return false;
        }
        foreach ($sub->parameters as $i => $parameter) {
            $superParameter = $super->parameters[$i];
            if (!$parameter->optional && $superParameter->optional) {
                return false;
            }
            if (self::check($parameter->type, $superParameter->type)) {
                continue;
            }
            return false;
        }
        return true;
    }

    private static function checkIterable(IterableType $super, AbstractType $sub): bool
    {
        if ($sub instanceof ToIterableInterface) {
            $sub = $sub->toIterable();
        }
        if (!$sub instanceof IterableType) {
            return false;
        }
        return self::check($super->keyType, $sub->keyType)
            && self::check($super->valueType, $sub->valueType);
    }

    private static function checkSubUnion(AbstractType $super, UnionType $sub): bool
    {
        return self::check($super, $sub->left) && self::check($super, $sub->right);
    }

    private static function checkIntersection(IntersectionType $super, AbstractType $sub): bool
    {
        return self::check($super->left, $sub) && self::check($super->right, $sub);
    }

    private static function checkSubIntersection(AbstractType $super, IntersectionType $sub): bool
    {
        return self::check($super, $sub->left) && self::check($super, $sub->right);
    }
}
