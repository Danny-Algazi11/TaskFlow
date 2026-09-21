<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Infrastructure\Persistence\Models\Board;
use Illuminate\Support\Collection;

class EloquentBoardRepository implements BoardRepositoryInterface
{
    public function find(int $id): ?Board
    {
        return Board::find($id);
    }

    public function forWorkspace(int $workspaceId): Collection
    {
        return Board::where('workspace_id', $workspaceId)->get();
    }

    public function create(array $attributes): Board
    {
        return Board::create($attributes);
    }

    public function update(Board $board, array $attributes): Board
    {
        $board->update($attributes);

        return $board;
    }

    public function delete(Board $board): void
    {
        $board->delete();
    }
}
