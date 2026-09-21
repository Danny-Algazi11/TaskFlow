<?php

namespace App\Application\WorkspaceInvitation\UseCases;

use App\Application\WorkspaceInvitation\DTOs\AcceptInvitationData;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Domain\WorkspaceInvitation\Exceptions\InvitationEmailMismatchException;
use App\Domain\WorkspaceInvitation\Exceptions\InvitationNotFoundException;
use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;

class AcceptInvitationUseCase
{
    public function __construct(
        private readonly WorkspaceInvitationRepositoryInterface $invitations,
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @throws InvitationNotFoundException
     * @throws InvitationEmailMismatchException
     * @throws WorkspaceNotFoundException
     */
    public function execute(AcceptInvitationData $data): Workspace
    {
        $invitation = $this->invitations->findPendingByToken($data->token);

        if (! $invitation) {
            throw new InvitationNotFoundException();
        }

        if (strcasecmp($invitation->email, $data->actingUserEmail) !== 0) {
            throw new InvitationEmailMismatchException();
        }

        $workspace = $this->workspaces->find($invitation->workspace_id);

        if (! $workspace) {
            throw new WorkspaceNotFoundException();
        }

        // Defensive: guards a double-accept race, or the rare case where
        // the invitee already independently joined some other way — the
        // workspace_user table has a unique(workspace_id, user_id)
        // constraint, so attaching again would otherwise throw.
        if (! $this->workspaces->isMember($workspace, $data->actingUserId)) {
            $this->workspaces->addMember($workspace, $data->actingUserId, $invitation->role);
        }

        $this->invitations->markAccepted($invitation);

        return $workspace;
    }
}
