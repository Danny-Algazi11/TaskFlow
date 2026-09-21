<?php

namespace App\Application\Label\DTOs;

final class UpdateLabelData
{
    public function __construct(
        public readonly int $labelId,
        public readonly int $actingUserId,
        public readonly string $name,
        public readonly string $color,
    ) {
    }
}
