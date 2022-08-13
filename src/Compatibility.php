<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use LogicException;

use function get_class;

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
            MixedType::class => true,
            NeverType::class => false,
            ScalarType::class => self::checkScalar($sub),
            StringType::class => self::checkString($super, $sub),
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
        if ($sub instanceof ClassStringType) {
            if ($super->numeric) {
                return false;
            }
            return true;
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
            || $sub instanceof ClassStringType
            || ($sub instanceof UnionType && self::checkScalar($sub->left) && self::checkScalar($sub->right));
    }
}
