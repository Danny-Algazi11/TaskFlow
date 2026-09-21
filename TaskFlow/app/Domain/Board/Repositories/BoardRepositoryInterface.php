<?php

namespace App\Domain\Board\Repositories;

use App\Infrastructure\Persistence\Models\Board;
use Illuminate\Support\Collection;

interface BoardRepositoryInterface
{
    public function find(int $id): ?Board;

    /**
     * @return Collection<int, Board>
     */
    public function forWorkspace(int $workspaceId): Collection;

    public function create(array $attributes): Board;

    public function update(Board $board, array $attributes): Board;

    public function delete(Board $board): void;
}
