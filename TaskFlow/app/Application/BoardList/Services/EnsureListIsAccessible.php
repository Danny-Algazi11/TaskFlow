<?php

namespace App\Application\BoardList\Services;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\BoardList;

/**
 * Find the list, then defer to EnsureBoardIsAccessible for its board —
 * this class never talks to the Workspace repository itself. Each layer
 * in this chain (Card → List → Board → Workspace) only needs to know
 * about the one directly beneath it.
 */
class EnsureListIsAccessible
{
    public function __construct(
        private readonly BoardListRepositoryInterface $lists,
        private readonly EnsureBoardIsAccessible $ensureBoardIsAccessible,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $listId, int $actingUserId): BoardList
    {
        $list = $this->lists->find($listId);

        if (! $list) {
            throw new BoardListNotFoundException();
        }

        ($this->ensureBoardIsAccessible)($list->board_id, $actingUserId);

        return $list;
    }
}
