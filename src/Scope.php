<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use RuntimeException;

use function count;
use function implode;
use function sprintf;

final class Scope
{
    /** @var array<string, AbstractType|callable(list<AbstractType>): AbstractType> */
    private array $types = [];

    public static function global(): self
    {
        $scope = new self();
        $scope->register('array', $scope->map(...));
        $scope->register('array-key', new UnionType(new StringType(), new IntType()));
        $scope->register('class-string', $scope->classString(...));
        $scope->register('bool', new BoolType());
        $scope->register('false', new BoolType(false));
        $scope->register('float', new FloatType());
        $scope->register('iterable', $scope->iterable(...));
        $scope->register('list', self::list(...));
        $scope->register('mixed', new MixedType());
        $scope->register('negative-int', IntType::max(-1));
        $scope->register('never', new NeverType());
        $scope->register('non-empty-array', $scope->nonEmptyMap(...));
        $scope->register('non-empty-list', $scope->nonEmptyList(...));
        $scope->register('non-empty-string', StringType::nonEmpty());
        $scope->register('null', new NullType());
        $scope->register('numeric-string', StringType::numeric());
        $scope->register('positive-int', IntType::min(1));
        $scope->register('resource', new ResourceType());
        $scope->register('scalar', new ScalarType());
        $scope->register('string', new StringType());
        $scope->register('true', new BoolType(true));
        $scope->register('void', new VoidType());
        return $scope;
    }

    /**
     * @param list<AbstractType> $types
     */
    private static function list(array $types, bool $nonEmpty = false): ListType
    {
        $numberOfTypes = count($types);
        if ($numberOfTypes !== 1) {
            throw new RuntimeException(
                sprintf(
                    'The list type takes exactly one type parameter, %s (%s) given',
                    $numberOfTypes,
                    implode(', ', $types),
                ),
            );
        }
        return new ListType($types[0], $nonEmpty);
    }

    /**
     * @param list<AbstractType> $types
     */
    private function nonEmptyList(array $types): ListType
    {
        return self::list($types, true);
    }

    /**
     * @param list<AbstractType> $typeParameters
     */
    public function getType(string $name, array $typeParameters = []): AbstractType
    {
        $type = $this->types[$name] ?? null;
        if ($type === null) {
            throw new RuntimeException(sprintf('Unknown type %s', $name));
        }
        return $type instanceof AbstractType ? $type : $type($typeParameters);
    }

    /**
     * @param non-empty-string $name
     * @param AbstractType|callable(list<AbstractType>): AbstractType $type
     */
    public function register(string $name, AbstractType|callable $type): void
    {
        $this->types[$name] = $type;
    }

    /**
     * @param list<AbstractType> $typeParameters
     */
    private function map(array $typeParameters, bool $nonEmpty = false): MapType
    {
        [$keyType, $valueType] = match (count($typeParameters)) {
            0 => [$this->getType('array-key'), $this->getType('mixed')],
            1 => [$this->getType('array-key'), $typeParameters[0]],
            2 => [$typeParameters[0], $typeParameters[1]],
            default => throw new RuntimeException(
                'Array types must take one of the following forms: ' .
                'array, array<ValueType>, array<KeyType, ValueType>',
            ),
        };
        return new MapType($keyType, $valueType, $nonEmpty);
    }

    /**
     * @param list<AbstractType> $typeParameters
     */
    private function nonEmptyMap(array $typeParameters): MapType
    {
        return $this->map($typeParameters, true);
    }

    /**
     * @param list<AbstractType> $typeParameters
     */
    private function iterable(array $typeParameters): IterableType
    {
        [$keyType, $valueType] = match (count($typeParameters)) {
            0 => [$this->getType('mixed'), $this->getType('mixed')],
            1 => [$this->getType('mixed'), $typeParameters[0]],
            2 => [$typeParameters[0], $typeParameters[1]],
            default => throw new RuntimeException(
                'Iterable types must take one of the following forms: ' .
                'iterable, iterable<ValueType>, iterable<KeyType, ValueType>',
            ),
        };
        return new IterableType($keyType, $valueType);
    }

    /**
     * @param list<AbstractType> $typeParameters
     */
    private function classString(array $typeParameters): ClassStringType
    {
        $typeParam = match (count($typeParameters)) {
            0 => null,
            1 => $typeParameters[0],
            default => throw new RuntimeException('class-string takes zero or one type parameters'),
        };
        return new ClassStringType($typeParam);
    }
}
