<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Functional;

use PhpTypes\Types\ClassLikeType;
use PhpTypes\Types\Scope;
use PhpTypes\Types\Type;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InvalidTypesTest extends TestCase
{
    /**
     * @dataProvider invalidTypes
     */
    public function testInvalidTypes(string $typeString, string $expectedMessage): void
    {
        $scope = Scope::global();
        $scope->register('Foo', new ClassLikeType('Foo'));
        $scope->register('Bar', new ClassLikeType('Bar'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        Type::fromString($typeString, $scope);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function invalidTypes(): iterable
    {
        yield 'Boolean array key' => [
            'array<bool, string>',
            'Can\'t use bool as array key. Only strings and integers are allowed.',
        ];
        yield 'Iterable with three type parameters' => [
            'iterable<string, int, bool>',
            'Iterable types must take one of the following forms: ' .
            'iterable, iterable<ValueType>, iterable<KeyType, ValueType>',
        ];
        yield 'Class string with two type parameters' => [
            'class-string<Foo, Bar>',
            'class-string takes zero or one type parameters',
        ];
        yield 'List with two type parameters' => [
            'list<string, int>',
            'The list type takes exactly one type parameter, 2 (string, int) given',
        ];
        yield 'Unknown identifier' => [
            'my-imaginary-type',
            'Unknown type my-imaginary-type',
        ];
        yield 'Unknown identifier with type parameters' => [
            'my-imaginary-type<string, int>',
            'Unknown type my-imaginary-type<string, int>',
        ];
        yield 'Map with three type parameters' => [
            'array<string, int, bool>',
            'Array types must take one of the following forms: '
            . 'array, array<ValueType>, array<KeyType, ValueType>. '
            . 'Got array<string, int, bool>',
        ];
        yield 'Int with invalid minimum' => [
            'int<Foo, 10>',
            'Invalid minimum value for int type: Foo. Must be an integer or "min".',
        ];
        yield 'Int with invalid maximum' => [
            'int<10, Foo>',
            'Invalid maximum value for int type: Foo. Must be an integer or "max".',
        ];
        yield 'Int with a single type parameter' => [
            'int<10>',
            'The int type takes exactly zero or two type parameters, 1 (10) given',
        ];
        yield 'Int with three type parameters' => [
            'int<10, 20, 30>',
            'The int type takes exactly zero or two type parameters, 3 (10, 20, 30) given',
        ];
    }
}
