<?php

namespace App\Application\Board\UseCases;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

class DeleteBoardUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureAccessible,
        private readonly BoardRepositoryInterface $boards,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $boardId, int $actingUserId): void
    {
        $board = ($this->ensureAccessible)($boardId, $actingUserId);

        $this->boards->delete($board);
    }
}
