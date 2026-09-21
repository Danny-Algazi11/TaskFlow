<?php

namespace App\Application\Board\DTOs;

final class CreateBoardData
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly int $actingUserId,
        public readonly string $name,
    ) {
    }
}
