<?php

namespace App\Application\ChecklistItem\DTOs;

final class ReorderChecklistItemsData
{
    /**
     * @param int[] $orderedItemIds
     */
    public function __construct(
        public readonly int $cardId,
        public readonly int $actingUserId,
        public readonly array $orderedItemIds,
    ) {
    }
}
