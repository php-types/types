<?php

declare(strict_types=1);

namespace PhpTypes\Types\Conversion;

use PhpTypes\Types\IterableType;
use PhpTypes\Types\NeverType;

interface ToIterableInterface
{
    public function toIterable(): IterableType|NeverType;
}
