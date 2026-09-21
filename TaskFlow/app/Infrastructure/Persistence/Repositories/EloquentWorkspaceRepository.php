<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;
use Illuminate\Support\Collection;

class EloquentWorkspaceRepository implements WorkspaceRepositoryInterface
{
    public function find(int $id): ?Workspace
    {
        return Workspace::find($id);
    }

    public function forUser(int $userId): Collection
    {
        return Workspace::whereHas(
            'members',
            fn ($query) => $query->where('users.id', $userId),
        )->get();
    }

    public function create(array $attributes): Workspace
    {
        return Workspace::create($attributes);
    }

    public function update(Workspace $workspace, array $attributes): Workspace
    {
        $workspace->update($attributes);

        return $workspace;
    }

    public function delete(Workspace $workspace): void
    {
        $workspace->delete();
    }

    public function membersOf(Workspace $workspace): Collection
    {
        return $workspace->members()->get();
    }

    public function addMember(Workspace $workspace, int $userId, string $role): void
    {
        $workspace->members()->attach($userId, ['role' => $role]);
    }

    public function isMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->members()->where('users.id', $userId)->exists();
    }

    public function memberRole(Workspace $workspace, int $userId): ?string
    {
        $member = $workspace->members()->where('users.id', $userId)->first();

        return $member?->pivot->role;
    }
}
