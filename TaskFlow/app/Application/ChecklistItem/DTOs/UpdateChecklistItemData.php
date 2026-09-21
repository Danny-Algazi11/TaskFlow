<?php

namespace App\Application\ChecklistItem\DTOs;

final class UpdateChecklistItemData
{
    public function __construct(
        public readonly int $itemId,
        public readonly int $actingUserId,
        public readonly string $title,
    ) {
    }
}
