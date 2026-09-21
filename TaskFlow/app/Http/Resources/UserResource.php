<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Makes the API's public shape of a User an explicit, reviewable contract
 * instead of "whatever Eloquent's toArray() + #[Hidden] happens to expose".
 * Same fields the old response returned (password/remember_token were
 * already hidden) — just spelled out on purpose now.
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
