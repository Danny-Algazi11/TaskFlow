<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Card;

class ViewCardUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureAccessible,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $cardId, int $actingUserId): Card
    {
        return ($this->ensureAccessible)($cardId, $actingUserId);
    }
}
