<?php

namespace App\Application\Card\DTOs;

final class UpdateCardData
{
    public function __construct(
        public readonly int $cardId,
        public readonly int $actingUserId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $dueDate,
    ) {
    }
}
