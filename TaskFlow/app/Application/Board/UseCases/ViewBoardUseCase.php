<?php

namespace App\Application\Board\UseCases;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Board;

class ViewBoardUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureAccessible,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $boardId, int $actingUserId): Board
    {
        return ($this->ensureAccessible)($boardId, $actingUserId);
    }
}
