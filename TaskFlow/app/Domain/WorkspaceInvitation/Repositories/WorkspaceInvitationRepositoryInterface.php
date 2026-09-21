<?php

namespace App\Domain\WorkspaceInvitation\Repositories;

use App\Infrastructure\Persistence\Models\WorkspaceInvitation;
use Illuminate\Support\Collection;

interface WorkspaceInvitationRepositoryInterface
{
    public function find(int $id): ?WorkspaceInvitation;

    /**
     * Only ever returns an invitation that hasn't been accepted yet — an
     * accepted (or nonexistent) token is indistinguishable to a caller,
     * both are "no such invitation to accept".
     */
    public function findPendingByToken(string $token): ?WorkspaceInvitation;

    public function findPendingByWorkspaceAndEmail(int $workspaceId, string $email): ?WorkspaceInvitation;

    /**
     * @return Collection<int, WorkspaceInvitation>
     */
    public function pendingForWorkspace(int $workspaceId): Collection;

    public function create(array $attributes): WorkspaceInvitation;

    public function markAccepted(WorkspaceInvitation $invitation): void;

    public function delete(WorkspaceInvitation $invitation): void;
}
