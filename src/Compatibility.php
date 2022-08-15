<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use LogicException;

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
        $superClass = get_class($super);
        return match ($superClass) {
            BoolType::class => self::checkBool($super, $sub),
            ClassLikeType::class => self::checkClassLike($super, $sub),
            ClassStringType::class => self::checkClassString($super, $sub),
            FloatType::class => self::checkFloat($sub),
            IntLiteralType::class => self::checkIntLiteral($super, $sub),
            IntType::class => self::checkInt($super, $sub),
            ListType::class => self::checkList($super, $sub),
            MapType::class => self::checkMap($super, $sub),
            MixedType::class => true,
            NeverType::class => false,
            NullType::class => $sub instanceof NullType,
            ScalarType::class => self::checkScalar($sub),
            StringLiteralType::class => self::checkStringLiteral($super, $sub),
            StringType::class => self::checkString($super, $sub),
            StructType::class => self::checkStruct($super, $sub),
            TupleType::class => self::checkTuple($super, $sub),
            UnionType::class => self::checkUnion($super, $sub),
            default => throw new LogicException(sprintf('Unsupported type "%s"', $superClass)),
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
        if ($sub instanceof UnionType) {
            $superTypes = $super->flatten();
            foreach ($sub->flatten() as $type) {
                if (self::isSubtypeOfAny($type, $superTypes)) {
                    continue;
                }
                return false;
            }
            return true;
        }
        return self::check($super->left, $sub) || self::check($super->right, $sub);
    }

    /**
     * @param list<AbstractType> $haystack
     */
    private static function isSubtypeOfAny(AbstractType $type, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if (!self::check($item, $type)) {
                continue;
            }
            return true;
        }
        return false;
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
            || $sub instanceof ClassStringType
            || ($sub instanceof UnionType && self::checkScalar($sub->left) && self::checkScalar($sub->right));
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
        if ($sub instanceof ListType || $sub instanceof TupleType || $sub instanceof StructType) {
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
            $subMember = $sub->members[$name] ?? null;
            if ($subMember === null) {
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
}
