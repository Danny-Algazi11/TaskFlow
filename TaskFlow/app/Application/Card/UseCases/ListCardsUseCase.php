<?php

namespace App\Application\Card\UseCases;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Card;
use Illuminate\Support\Collection;

class ListCardsUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureListIsAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @return Collection<int, Card>
     *
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $listId, int $actingUserId): Collection
    {
        ($this->ensureListIsAccessible)($listId, $actingUserId);

        return $this->cards->forList($listId);
    }
}
