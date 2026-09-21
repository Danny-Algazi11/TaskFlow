<?php

namespace App\Application\Board\DTOs;

final class UpdateBoardData
{
    public function __construct(
        public readonly int $boardId,
        public readonly int $actingUserId,
        public readonly string $name,
    ) {
    }
}
