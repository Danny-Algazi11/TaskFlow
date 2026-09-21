<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Broadcasting\Events\Card\CardDeleted;

class DeleteCardUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $cardId, int $actingUserId): void
    {
        $card = ($this->ensureAccessible)($cardId, $actingUserId);

        // Captured before delete() — the event carries plain ids because
        // there's nothing left to serialize from once the row is gone.
        $boardListId = (int) $card->board_list_id;
        $boardId = (int) $card->list->board_id;

        $this->cards->delete($card);

        event(new CardDeleted((int) $card->id, $boardListId, $boardId));
    }
}
