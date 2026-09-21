<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Persistence\Models\WorkspaceInvitation;
use Illuminate\Support\Collection;

class EloquentWorkspaceInvitationRepository implements WorkspaceInvitationRepositoryInterface
{
    public function find(int $id): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::find($id);
    }

    public function findPendingByToken(string $token): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::where('token', $token)->whereNull('accepted_at')->first();
    }

    public function findPendingByWorkspaceAndEmail(int $workspaceId, string $email): ?WorkspaceInvitation
    {
        return WorkspaceInvitation::where('workspace_id', $workspaceId)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->first();
    }

    public function pendingForWorkspace(int $workspaceId): Collection
    {
        return WorkspaceInvitation::where('workspace_id', $workspaceId)->whereNull('accepted_at')->get();
    }

    public function create(array $attributes): WorkspaceInvitation
    {
        return WorkspaceInvitation::create($attributes);
    }

    public function markAccepted(WorkspaceInvitation $invitation): void
    {
        // Set directly rather than mass-assigned via update(['accepted_at'
        // => ...]) — accepted_at is deliberately NOT in the model's
        // $fillable list, since it's an internal state transition this one
        // method controls, not a field that should ever be settable from
        // arbitrary array input (e.g. create()'s attributes).
        $invitation->accepted_at = now();
        $invitation->save();
    }

    public function delete(WorkspaceInvitation $invitation): void
    {
        $invitation->delete();
    }
}
