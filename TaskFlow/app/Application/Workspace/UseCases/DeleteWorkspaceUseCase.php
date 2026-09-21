<?php

namespace App\Application\Workspace\UseCases;

use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;

/**
 * Deleting is owner-only — stricter again than "admin", since any admin
 * could otherwise delete a workspace out from under its owner. Three
 * Use Cases, three different authorization rules: membership, role,
 * ownership. Each is a distinct business decision, which is exactly why
 * hard-coding one generic "canAccessWorkspace()" check would have been
 * the wrong abstraction.
 */
class DeleteWorkspaceUseCase
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): void
    {
        $workspace = $this->workspaces->find($workspaceId);

        if (! $workspace) {
            throw new WorkspaceNotFoundException();
        }

        if ((int) $workspace->owner_id !== $actingUserId) {
            throw new WorkspaceAccessDeniedException('Only the workspace owner can delete it.');
        }

        $this->workspaces->delete($workspace);
    }
}
