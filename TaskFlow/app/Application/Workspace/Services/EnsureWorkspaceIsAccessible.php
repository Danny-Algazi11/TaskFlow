<?php

namespace App\Application\Workspace\Services;

use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;

/**
 * Find the workspace, confirm the acting user is a member. Extracted once
 * this exact "find + check membership" pair showed up identically in
 * ViewWorkspaceUseCase, CreateBoardUseCase, and ListWorkspaceBoardsUseCase
 * — three real occurrences, not a guess at future reuse. It's the same
 * root check EnsureBoardIsAccessible and friends build on, one level up.
 */
class EnsureWorkspaceIsAccessible
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $workspaceId, int $actingUserId): Workspace
    {
        $workspace = $this->workspaces->find($workspaceId);

        if (! $workspace) {
            throw new WorkspaceNotFoundException();
        }

        if (! $this->workspaces->isMember($workspace, $actingUserId)) {
            throw new WorkspaceAccessDeniedException();
        }

        return $workspace;
    }
}
