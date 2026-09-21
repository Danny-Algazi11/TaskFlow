<?php

namespace App\Application\BoardList\DTOs;

final class UpdateBoardListData
{
    public function __construct(
        public readonly int $listId,
        public readonly int $actingUserId,
        public readonly string $name,
    ) {
    }
}
