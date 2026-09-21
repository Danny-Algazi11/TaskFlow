<?php

namespace App\Application\Label\DTOs;

final class CreateLabelData
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $actingUserId,
        public readonly string $name,
        public readonly string $color,
    ) {
    }
}
