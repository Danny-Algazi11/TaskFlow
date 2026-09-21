<?php

namespace App\Application\BoardList\UseCases;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

/**
 * Deleting a list cascades to its cards at the database level (the
 * board_lists → cards foreign key is cascadeOnDelete in the migration) —
 * no application-level cleanup needed here.
 */
class DeleteBoardListUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureAccessible,
        private readonly BoardListRepositoryInterface $lists,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $listId, int $actingUserId): void
    {
        $list = ($this->ensureAccessible)($listId, $actingUserId);

        $this->lists->delete($list);
    }
}
