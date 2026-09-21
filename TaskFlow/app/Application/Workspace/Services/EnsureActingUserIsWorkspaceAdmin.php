<?php

namespace App\Application\Workspace\Services;

use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;

/**
 * Builds on EnsureWorkspaceIsAccessible the same way EnsureCardIsAccessible
 * builds on EnsureListIsAccessible — a stricter check layered on top of a
 * looser one, not a rewrite of it. Extracted once "must be admin" was
 * about to appear a 4th time (rename, invite, list invites, revoke
 * invite) — same "3rd+ real occurrence" threshold that justified
 * EnsureWorkspaceIsAccessible itself in Phase 4.
 */
class EnsureActingUserIsWorkspaceAdmin
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $workspaceId, int $actingUserId): Workspace
    {
        $workspace = ($this->ensureWorkspaceIsAccessible)($workspaceId, $actingUserId);

        if ($this->workspaces->memberRole($workspace, $actingUserId) !== 'admin') {
            throw new WorkspaceAccessDeniedException();
        }

        return $workspace;
    }
}
