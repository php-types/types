<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Functional;

use DirectoryIterator;
use LogicException;
use PhpTypes\Types\ClassLikeType;
use PhpTypes\Types\Compatibility;
use PhpTypes\Types\MixedType;
use PhpTypes\Types\Scope;
use PhpTypes\Types\Type;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_search;
use function explode;
use function in_array;
use function sort;
use function sprintf;

final class CompatibilityTest extends TestCase
{
    private static Scope $scope;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$scope = Scope::global();
        $fooInterface = new ClassLikeType('FooInterface');
        self::$scope->register('FooInterface', $fooInterface);
        self::$scope->register('Foo', new ClassLikeType('Foo', parents: [$fooInterface]));
    }

    /**
     * @return list<array{string, string}>
     */
    private static function compatibleTypes(): array
    {
        $types = [];
        foreach (self::filesInDirectory(__DIR__ . '/compatible-types/') as $file) {
            foreach (explode("\n", \Safe\file_get_contents($file)) as $line) {
                $isMatch = \Safe\preg_match('/^- `(?<sub>.+)` is a subtype of `(?<super>.+)`/', $line, $matches);
                if ($isMatch === 0) {
                    continue;
                }
                $types[] = [$matches['super'], $matches['sub']];
            }
        }
        return $types;
    }

    /**
     * @return iterable<int, string>
     */
    private static function filesInDirectory(string $directory): iterable
    {
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }

            yield $file->getPathname();
        }
    }

    /**
     * @return iterable<int, string>
     */
    private static function types(): iterable
    {
        foreach (self::typeFiles() as $file) {
            foreach (explode("\n", \Safe\file_get_contents($file)) as $line) {
                if ($line === '') {
                    continue;
                }
                yield $line;
            }
        }
    }

    /**
     * @return iterable<int, string>
     */
    private static function typeFiles(): iterable
    {
        return self::filesInDirectory(__DIR__ . '/types/');
    }

    /**
     * @return list<array{string, string}>
     */
    private static function aliases(): array
    {
        $aliases = [];
        foreach (explode("\n", \Safe\file_get_contents(__DIR__ . '/aliases.md')) as $line) {
            $isMatch = \Safe\preg_match('/^- `(?<a>.+)` is an alias of `(?<b>.+)`/', $line, $matches);
            if ($isMatch === 0) {
                continue;
            }
            $tuple = [$matches['a'], $matches['b']];
            sort($tuple);
            $aliases[] = $tuple;
        }
        return $aliases;
    }

    /**
     * @dataProvider compatibilityCases
     */
    public function testCompatibility(string $super, string $sub, bool $expected): void
    {
        $superType = Type::fromString($super, self::$scope);
        $subType = Type::fromString($sub, self::$scope);

        $message = $expected
            ? sprintf('Expected "%s" to be a subtype of "%s", but it is not', $sub, $super)
            : sprintf('Expected "%s" not to be a subtype of "%s", but it is', $sub, $super);
        self::assertSame($expected, Compatibility::check($superType, $subType), $message);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function compatibilityCases(): iterable
    {
        $compatibleTypes = self::compatibleTypes();
        foreach (self::types() as $super) {
            foreach (self::types() as $sub) {
                $compatibleTypesKey = array_search([$super, $sub], $compatibleTypes, true);
                $expected = $compatibleTypesKey !== false;
                $name = $expected
                    ? sprintf('%s is a subtype of %s', $sub, $super)
                    : sprintf('%s is not a subtype of %s', $sub, $super);
                yield $name => [$super, $sub, $expected];
                unset($compatibleTypes[$compatibleTypesKey]);
            }
        }
        if ($compatibleTypes === []) {
            return;
        }
        throw new LogicException(
            sprintf(
                "There are %s unchecked compatibility declarations:\n%s",
                count($compatibleTypes),
                implode(
                    "\n",
                    array_map(static function (array $compatibleType): string {
                        return sprintf('- `%s` is a subtype of `%s`', $compatibleType[1], $compatibleType[0]);
                    }, $compatibleTypes),
                ),
            )
        );
    }

    /**
     * @dataProvider allTypes
     */
    public function testEveryTypeIsCompatibleWithMixed(string $type): void
    {
        self::assertTrue(
            Compatibility::check(new MixedType(), Type::fromString($type, self::$scope)),
            sprintf('Expected "%s" to be a subtype of "mixed", but it is not', $type),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function allTypes(): iterable
    {
        foreach (self::types() as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @dataProvider aliasCases
     */
    public function testAliases(string $a, string $b, bool $expected): void
    {
        $aType = Type::fromString($a, self::$scope);
        $bType = Type::fromString($b, self::$scope);

        $isAlias = Compatibility::check($aType, $bType) && Compatibility::check($bType, $aType);

        $message = $expected
            ? sprintf('Expected "%s" to be an alias of "%s", but it is not', $b, $a)
            : sprintf('Expected "%s" not to be an alias of "%s", but it is', $b, $a);
        self::assertSame($expected, $isAlias, $message);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function aliasCases(): iterable
    {
        $aliases = self::aliases();
        $seen = [];
        foreach (self::types() as $a) {
            foreach (self::types() as $b) {
                if ($a === $b) {
                    continue;
                }
                $tuple = [$a, $b];
                sort($tuple);
                if (in_array($tuple, $seen, true)) {
                    continue;
                }
                $expected = array_search($tuple, $aliases, true) !== false;
                $name = $expected
                    ? sprintf('%s is an alias of %s', $b, $a)
                    : sprintf('%s is not an alias of %s', $b, $a);
                yield $name => [$a, $b, $expected];
                $seen[] = $tuple;
            }
        }
    }
}
