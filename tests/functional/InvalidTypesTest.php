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
    }
}
