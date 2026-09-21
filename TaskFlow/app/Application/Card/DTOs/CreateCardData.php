<?php

namespace App\Application\Card\DTOs;

final class CreateCardData
{
    public function __construct(
        public readonly int $listId,
        public readonly int $actingUserId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $dueDate,
    ) {
    }
}
