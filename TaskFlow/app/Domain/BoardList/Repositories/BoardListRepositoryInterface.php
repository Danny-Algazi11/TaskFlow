<?php

namespace App\Domain\BoardList\Repositories;

use App\Infrastructure\Persistence\Models\BoardList;
use Illuminate\Support\Collection;

interface BoardListRepositoryInterface
{
    public function find(int $id): ?BoardList;

    /**
     * @return Collection<int, BoardList> ordered by position
     */
    public function forBoard(int $boardId): Collection;

    public function create(array $attributes): BoardList;

    public function update(BoardList $list, array $attributes): BoardList;

    public function delete(BoardList $list): void;

    public function nextPosition(int $boardId): int;

    /**
     * Rewrite every list's position to match its index in $orderedListIds.
     * Callers are responsible for validating that the id set is exactly
     * this board's current lists — this method trusts it.
     */
    public function reorder(int $boardId, array $orderedListIds): void;
}
