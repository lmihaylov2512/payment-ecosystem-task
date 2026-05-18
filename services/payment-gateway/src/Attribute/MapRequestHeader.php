<?php

declare(strict_types=1);

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class MapRequestHeader
{
    public function __construct(
        public readonly string $name,
    ) {}
}
