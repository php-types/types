<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Functional;

use PhpTypes\Types\ClassLikeType;
use PhpTypes\Types\Scope;
use PhpTypes\Types\Type;
use PHPUnit\Framework\TestCase;

use function is_int;
use function is_numeric;
use function sprintf;

final class RoundtripTest extends TestCase
{
    private Scope $scope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scope = Scope::global();
        $this->scope->register('Foo', static fn(array $typeParameters) => new ClassLikeType('Foo', $typeParameters));
        $this->scope->register('Bar', new ClassLikeType('Bar'));
        $this->scope->register('Baz', new ClassLikeType('Baz'));
        $this->scope->register('Qux', new ClassLikeType('Qux'));
        $this->scope->register('Maroon5', new ClassLikeType('Maroon5'));
    }

    /**
     * @dataProvider cases
     */
    public function testRoundtrip(string $typeString, string $expected): void
    {
        $node = Type::fromString($typeString, $this->scope);

        self::assertSame($expected, (string)$node);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function cases(): iterable
    {
        $cases = [
            // Array
            'array<array-key, string>',
            'array<int, string>',
            'array<string, string>',
            'array<int<0, 10>, string>',
            'non-empty-array<array-key, string>',
            'non-empty-array<int, string>',
            'non-empty-array<string, string>',
            'non-empty-array<int<0, 10>, string>',
            // Bool
            'bool',
            'true',
            'false',
            // Callable
            'callable(): void',
            'callable(string): void',
            'callable(string, int): void',
            'callable(string, int=, bool=): void',
            // ClassLike
            'Foo<Bar, Baz, Qux>',
            'Maroon5',
            // Float
            'float',
            // Intersection
            'Foo & Bar',
            'Foo & Bar & Baz',
            // IntLiteral
            '42',
            '0',
            '-42',
            '5',
            // Int
            'int',
            'int<0, 10>',
            'int<min, 10>',
            'int<0, max>',
            'positive-int',
            'negative-int',
            // Iterable
            'iterable<int, string>',
            'iterable<string>' => 'iterable<mixed, string>',
            'iterable<mixed, bool>',
            'iterable<Foo, Bar>',
            // List
            'list<string>',
            'non-empty-list<string>',
            // Never
            'never',
            // Null
            'null',
            // StringLiteral
            "'foo'",
            '"foo"' => "'foo'",
            // Resource
            'resource',
            // String
            'string',
            'non-empty-string',
            'class-string',
            'class-string<Foo>',
            'numeric-string',
            // Struct
            'array{}',
            <<<'PHP'
            array{
                foo: string,
            }
            PHP,
            <<<'PHP'
            array{
                foo: string,
                bar?: string,
            }
            PHP,
            // Tuple
            'array{string}',
            'array{string, int}',
            'array{string, int, bool}',
            // Union
            'string | int',
            'string | int | bool',
            // Void
            'void',
        ];
        foreach ($cases as $from => $to) {
            if (is_int($from)) {
                $from = $to;
                $name = $to;
                if (is_numeric($name)) {
                    $name = 'Number ' . $name;
                }
            } else {
                $name = sprintf('%s -> %s', $from, $to);
            }
            yield $name => [$from, $to];
        }
    }
}
