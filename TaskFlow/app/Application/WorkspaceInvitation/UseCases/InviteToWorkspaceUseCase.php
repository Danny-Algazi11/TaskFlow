<?php

namespace App\Application\WorkspaceInvitation\UseCases;

use App\Application\Workspace\Services\EnsureActingUserIsWorkspaceAdmin;
use App\Application\WorkspaceInvitation\DTOs\CreateInvitationData;
use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Domain\WorkspaceInvitation\Exceptions\AlreadyAMemberException;
use App\Domain\WorkspaceInvitation\Exceptions\PendingInvitationAlreadyExistsException;
use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Persistence\Models\WorkspaceInvitation;
use Illuminate\Support\Str;

class InviteToWorkspaceUseCase
{
    public function __construct(
        private readonly EnsureActingUserIsWorkspaceAdmin $ensureIsAdmin,
        private readonly UserRepositoryInterface $users,
        private readonly WorkspaceRepositoryInterface $workspaces,
        private readonly WorkspaceInvitationRepositoryInterface $invitations,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws AlreadyAMemberException
     * @throws PendingInvitationAlreadyExistsException
     */
    public function execute(CreateInvitationData $data): WorkspaceInvitation
    {
        $workspace = ($this->ensureIsAdmin)($data->workspaceId, $data->actingUserId);

        $existingUser = $this->users->findByEmail($data->email);

        if ($existingUser && $this->workspaces->isMember($workspace, $existingUser->id)) {
            throw new AlreadyAMemberException();
        }

        if ($this->invitations->findPendingByWorkspaceAndEmail($data->workspaceId, $data->email)) {
            throw new PendingInvitationAlreadyExistsException();
        }

        // In a real deployment this is where a Mailable carrying the accept
        // link (token) would be dispatched. No mail transport is set up for
        // this project yet, so the token comes back in the response instead
        // — fine for now since the invite flow isn't wired into the React
        // frontend either.
        return $this->invitations->create([
            'workspace_id' => $data->workspaceId,
            'email' => $data->email,
            'role' => $data->role,
            'token' => Str::random(40),
            'invited_by' => $data->actingUserId,
        ]);
    }
}
