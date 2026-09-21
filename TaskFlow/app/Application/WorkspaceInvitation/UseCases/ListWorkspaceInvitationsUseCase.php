<?php

namespace App\Application\WorkspaceInvitation\UseCases;

use App\Application\Workspace\Services\EnsureActingUserIsWorkspaceAdmin;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Persistence\Models\WorkspaceInvitation;
use Illuminate\Support\Collection;

class ListWorkspaceInvitationsUseCase
{
    public function __construct(
        private readonly EnsureActingUserIsWorkspaceAdmin $ensureIsAdmin,
        private readonly WorkspaceInvitationRepositoryInterface $invitations,
    ) {
    }

    /**
     * @return Collection<int, WorkspaceInvitation>
     *
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): Collection
    {
        ($this->ensureIsAdmin)($workspaceId, $actingUserId);

        return $this->invitations->pendingForWorkspace($workspaceId);
    }
}
