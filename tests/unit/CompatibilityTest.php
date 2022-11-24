<?php

declare(strict_types=1);

namespace PhpTypes\Types\Tests\Unit;

use LogicException;
use PhpTypes\Ast\Node\IdentifierNode;
use PhpTypes\Ast\Node\NodeInterface;
use PhpTypes\Types\AbstractType;
use PhpTypes\Types\Compatibility;
use PhpTypes\Types\IntType;
use PHPUnit\Framework\TestCase;

final class CompatibilityTest extends TestCase
{
    public function testThrowsWhenGivenAnUnknownSuperType(): void
    {
        $super = new class extends AbstractType {
            public function toNode(): NodeInterface
            {
                return new IdentifierNode('my-special-type');
            }
        };

        $this->expectException(LogicException::class);

        Compatibility::check($super, new IntType());
    }
}
