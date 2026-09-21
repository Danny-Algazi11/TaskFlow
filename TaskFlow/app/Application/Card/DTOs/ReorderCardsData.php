<?php

namespace App\Application\Card\DTOs;

final class ReorderCardsData
{
    /**
     * @param int[] $orderedCardIds
     */
    public function __construct(
        public readonly int $listId,
        public readonly int $actingUserId,
        public readonly array $orderedCardIds,
    ) {
    }
}
