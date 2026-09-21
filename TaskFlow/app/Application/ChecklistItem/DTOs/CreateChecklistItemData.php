<?php

namespace App\Application\ChecklistItem\DTOs;

final class CreateChecklistItemData
{
    public function __construct(
        public readonly int $cardId,
        public readonly int $actingUserId,
        public readonly string $title,
    ) {
    }
}
