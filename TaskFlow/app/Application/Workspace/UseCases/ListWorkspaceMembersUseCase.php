<?php

namespace App\Application\Workspace\UseCases;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Collection;

/**
 * Membership, not admin, is the bar here — any member can see who else is
 * in the workspace, same as viewing the workspace itself.
 */
class ListWorkspaceMembersUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @return Collection<int, User>
     *
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): Collection
    {
        $workspace = ($this->ensureWorkspaceIsAccessible)($workspaceId, $actingUserId);

        return $this->workspaces->membersOf($workspace);
    }
}
