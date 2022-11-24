<?php

declare(strict_types=1);

namespace PhpTypes\Types;

use PhpTypes\Ast\Node\IntersectionNode;
use PhpTypes\Ast\Node\NodeInterface;

use function array_shift;
use function assert;
use function count;

final class IntersectionType extends AbstractType
{
    private function __construct(public readonly AbstractType $left, public readonly AbstractType $right)
    {
    }

    public static function create(AbstractType $left, AbstractType $right): AbstractType
    {
        $parts = array_merge(
            $left instanceof self ? $left->flatten() : [$left],
            $right instanceof self ? $right->flatten() : [$right],
        );
        $structs = [];
        foreach ($parts as $index => $part) {
            if (!$part instanceof StructType) {
                continue;
            }
            $structs[] = $part;
            unset($parts[$index]);
        }
        if ($structs !== []) {
            $parts[] = StructType::merge($structs);
        }
        assert($parts !== []);
        return self::unflatten($parts);
    }

    /**
     * @param non-empty-array<array-key, AbstractType> $types
     */
    private static function unflatten(array $types): AbstractType
    {
        return match (count($types)) {
            1 => array_shift($types),
            2 => new self(array_shift($types), array_shift($types)),
            default => new self(array_shift($types), self::unflatten($types)),
        };
    }

    public function toNode(): NodeInterface
    {
        return new IntersectionNode($this->left->toNode(), $this->right->toNode());
    }

    /**
     * @return non-empty-list<AbstractType>
     */
    private function flatten(): array
    {
        $leftParts = $this->left instanceof self ? $this->left->flatten() : [$this->left];
        $rightParts = $this->right instanceof self ? $this->right->flatten() : [$this->right];
        return array_merge($leftParts, $rightParts);
    }
}
