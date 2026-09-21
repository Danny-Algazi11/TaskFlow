<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Infrastructure\Persistence\Models\BoardList;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentBoardListRepository implements BoardListRepositoryInterface
{
    public function find(int $id): ?BoardList
    {
        return BoardList::find($id);
    }

    public function forBoard(int $boardId): Collection
    {
        return BoardList::where('board_id', $boardId)->orderBy('position')->get();
    }

    public function create(array $attributes): BoardList
    {
        return BoardList::create($attributes);
    }

    public function update(BoardList $list, array $attributes): BoardList
    {
        $list->update($attributes);

        return $list;
    }

    public function delete(BoardList $list): void
    {
        $list->delete();
    }

    public function nextPosition(int $boardId): int
    {
        // max() returns null when the board has no lists yet — casting
        // that to (int) 0 and adding 1 would start the first list at
        // position 1 instead of 0, so null is handled explicitly.
        $max = BoardList::where('board_id', $boardId)->max('position');

        return $max === null ? 0 : $max + 1;
    }

    public function reorder(int $boardId, array $orderedListIds): void
    {
        DB::transaction(function () use ($orderedListIds) {
            foreach ($orderedListIds as $position => $listId) {
                BoardList::where('id', $listId)->update(['position' => $position]);
            }
        });
    }
}
