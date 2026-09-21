<?php

namespace App\Application\BoardList\DTOs;

final class CreateBoardListData
{
    public function __construct(
        public readonly int $boardId,
        public readonly int $actingUserId,
        public readonly string $name,
    ) {
    }
}
