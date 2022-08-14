<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Functional;

use DirectoryIterator;
use PhpTypes\Types\ClassLikeType;
use PhpTypes\Types\Compatibility;
use PhpTypes\Types\MixedType;
use PhpTypes\Types\Scope;
use PhpTypes\Types\Type;
use PHPUnit\Framework\TestCase;

use function explode;
use function file_get_contents;
use function in_array;
use function sprintf;

final class CompatibilityTest extends TestCase
{
    private Scope $scope;

    /**
     * @return list<array{string, string}>
     */
    private static function compatibleTypes(): array
    {
        $types = [];
        foreach (self::filesInDirectory(__DIR__ . '/compatible-types/') as $file) {
            foreach (explode("\n", file_get_contents($file)) as $line) {
                $isMatch = \Safe\preg_match('/^- `(?<sub>.+)` is a subtype of `(?<super>.+)`/', $line, $matches);
                if (!$isMatch) {
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
            foreach (explode("\n", file_get_contents($file)) as $line) {
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
     * @dataProvider cases
     */
    public function testCompatibility(string $super, string $sub, bool $expected): void
    {
        $superType = Type::fromString($super, $this->scope);
        $subType = Type::fromString($sub, $this->scope);

        $message = $expected
            ? sprintf('Expected "%s" to be a subtype of "%s", but it is not', $sub, $super)
            : sprintf('Expected "%s" not to be a subtype of "%s", but it is', $sub, $super);
        self::assertSame($expected, Compatibility::check($superType, $subType), $message);
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function cases(): iterable
    {
        $compatibleTypes = self::compatibleTypes();
        foreach (self::types() as $super) {
            foreach (self::types() as $sub) {
                $expected = in_array([$super, $sub], $compatibleTypes, true);
                $name = $expected
                    ? sprintf('%s is a subtype of %s', $sub, $super)
                    : sprintf('%s is not a subtype of %s', $sub, $super);
                yield $name => [$super, $sub, $expected];
            }
        }
    }

    /**
     * @dataProvider allTypes
     */
    public function testEveryTypeIsCompatibleWithMixed(string $type): void
    {
        self::assertTrue(
            Compatibility::check(new MixedType(), Type::fromString($type, $this->scope)),
            sprintf('Expected "%s" to be a subtype of "mixed", but it is not', $type),
        );
    }

    /**
     * @return iterable<int, array{string}>
     */
    public function allTypes(): iterable
    {
        foreach (self::types() as $type) {
            yield $type => [$type];
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->scope = Scope::global();
        $fooInterface = new ClassLikeType('FooInterface');
        $this->scope->register('FooInterface', $fooInterface);
        $this->scope->register('Foo', new ClassLikeType('Foo', parents: [$fooInterface]));
    }
}
