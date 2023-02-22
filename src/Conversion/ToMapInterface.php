<?php

declare(strict_types=1);

namespace PhpTypes\Types\Conversion;

use PhpTypes\Types\MapType;
use PhpTypes\Types\NeverType;

interface ToMapInterface
{
    public function toMap(): MapType|NeverType;
}
