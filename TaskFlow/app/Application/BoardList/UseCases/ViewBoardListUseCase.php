<?php

namespace App\Application\BoardList\UseCases;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\BoardList;

class ViewBoardListUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureAccessible,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $listId, int $actingUserId): BoardList
    {
        return ($this->ensureAccessible)($listId, $actingUserId);
    }
}
