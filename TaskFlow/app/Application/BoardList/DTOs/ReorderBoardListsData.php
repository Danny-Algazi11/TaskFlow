<?php

namespace App\Application\BoardList\DTOs;

final class ReorderBoardListsData
{
    /**
     * @param int[] $orderedListIds
     */
    public function __construct(
        public readonly int $boardId,
        public readonly int $actingUserId,
        public readonly array $orderedListIds,
    ) {
    }
}
