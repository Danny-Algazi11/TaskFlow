<?php

namespace App\Application\Card\Services;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Card;

class EnsureCardIsAccessible
{
    public function __construct(
        private readonly CardRepositoryInterface $cards,
        private readonly EnsureListIsAccessible $ensureListIsAccessible,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $cardId, int $actingUserId): Card
    {
        $card = $this->cards->find($cardId);

        if (! $card) {
            throw new CardNotFoundException();
        }

        ($this->ensureListIsAccessible)($card->board_list_id, $actingUserId);

        return $card;
    }
}
