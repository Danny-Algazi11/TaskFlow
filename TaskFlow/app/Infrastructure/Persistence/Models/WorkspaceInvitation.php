<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

// accepted_at is deliberately excluded — it's an internal state transition
// (see EloquentWorkspaceInvitationRepository::markAccepted), not a field
// any create/update call should be able to set via mass assignment.
#[Fillable(['workspace_id', 'email', 'role', 'token', 'invited_by'])]
#[Hidden(['token'])]
class WorkspaceInvitation extends Model
{
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function invitedBy()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
