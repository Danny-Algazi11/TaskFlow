<?php

namespace App\Application\Board\UseCases;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Board;
use Illuminate\Support\Collection;

class ListWorkspaceBoardsUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly BoardRepositoryInterface $boards,
    ) {
    }

    /**
     * @return Collection<int, Board>
     *
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): Collection
    {
        ($this->ensureWorkspaceIsAccessible)($workspaceId, $actingUserId);

        return $this->boards->forWorkspace($workspaceId);
    }
}
