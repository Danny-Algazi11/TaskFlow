<?php

namespace App\Domain\Workspace\Repositories;

use App\Infrastructure\Persistence\Models\Workspace;
use Illuminate\Support\Collection;

interface WorkspaceRepositoryInterface
{
    public function find(int $id): ?Workspace;

    /**
     * @return Collection<int, Workspace>
     */
    public function forUser(int $userId): Collection;

    /**
     * @return Collection<int, \App\Infrastructure\Persistence\Models\User> each with ->pivot->role set
     */
    public function membersOf(Workspace $workspace): Collection;

    public function create(array $attributes): Workspace;

    public function update(Workspace $workspace, array $attributes): Workspace;

    public function delete(Workspace $workspace): void;

    public function addMember(Workspace $workspace, int $userId, string $role): void;

    public function isMember(Workspace $workspace, int $userId): bool;

    public function memberRole(Workspace $workspace, int $userId): ?string;
}
