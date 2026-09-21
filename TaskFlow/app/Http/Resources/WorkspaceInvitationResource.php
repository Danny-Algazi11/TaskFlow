<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately never includes the raw token — that's a one-time reveal
 * handled separately by the controller right after creation (see
 * WorkspaceInvitationController::store), the same way an API key is shown
 * once at creation and never again in a listing.
 */
class WorkspaceInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspace_id,
            'email' => $this->email,
            'role' => $this->role,
            'invited_by' => $this->invited_by,
            'accepted_at' => $this->accepted_at,
            'created_at' => $this->created_at,
        ];
    }
}
