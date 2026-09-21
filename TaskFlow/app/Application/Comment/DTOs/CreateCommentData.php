<?php

namespace App\Application\Comment\DTOs;

final class CreateCommentData
{
    public function __construct(
        public readonly int $cardId,
        public readonly int $actingUserId,
        public readonly string $body,
    ) {
    }
}
