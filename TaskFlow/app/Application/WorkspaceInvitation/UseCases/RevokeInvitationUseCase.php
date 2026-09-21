<?php

namespace App\Application\WorkspaceInvitation\UseCases;

use App\Application\Workspace\Services\EnsureActingUserIsWorkspaceAdmin;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\WorkspaceInvitation\Exceptions\InvitationNotFoundException;
use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;

class RevokeInvitationUseCase
{
    public function __construct(
        private readonly EnsureActingUserIsWorkspaceAdmin $ensureIsAdmin,
        private readonly WorkspaceInvitationRepositoryInterface $invitations,
    ) {
    }

    /**
     * @throws InvitationNotFoundException
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $invitationId, int $actingUserId): void
    {
        $invitation = $this->invitations->find($invitationId);

        // An already-accepted invitation is history, not something left to
        // revoke — treated the same as "doesn't exist" for this action.
        if (! $invitation || $invitation->accepted_at !== null) {
            throw new InvitationNotFoundException();
        }

        ($this->ensureIsAdmin)($invitation->workspace_id, $actingUserId);

        $this->invitations->delete($invitation);
    }
}
