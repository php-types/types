<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Functional;

use DirectoryIterator;
use PhpTypes\Types\ClassLikeType;
use PhpTypes\Types\Compatibility;
use PhpTypes\Types\Scope;
use PhpTypes\Types\Type;
use PHPUnit\Framework\TestCase;

use function explode;
use function file_get_contents;
use function in_array;
use function sprintf;

final class CompatibilityTest extends TestCase
{
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
     * @dataProvider cases
     */
    public function testCompatibility(string $super, string $sub, bool $expected): void
    {
        $scope = Scope::global();
        $fooInterface = new ClassLikeType('FooInterface');
        $scope->register('FooInterface', $fooInterface);
        $scope->register('Foo', new ClassLikeType('Foo', parents: [$fooInterface]));
        $superType = Type::fromString($super, $scope);
        $subType = Type::fromString($sub, $scope);

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
        foreach ($this->types() as $super) {
            foreach ($this->types() as $sub) {
                $expected = in_array([$super, $sub], $compatibleTypes, true);
                $name = $expected
                    ? sprintf('%s is a subtype of %s', $sub, $super)
                    : sprintf('%s is not a subtype of %s', $sub, $super);
                yield $name => [$super, $sub, $expected];
            }
        }
    }

    /**
     * @return iterable<int, string>
     */
    private function types(): iterable
    {
        foreach ($this->typeFiles() as $file) {
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
    private function typeFiles(): iterable
    {
        return self::filesInDirectory(__DIR__ . '/types/');
    }
}
