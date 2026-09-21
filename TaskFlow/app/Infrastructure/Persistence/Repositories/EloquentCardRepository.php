<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Infrastructure\Persistence\Models\Card;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentCardRepository implements CardRepositoryInterface
{
    public function find(int $id): ?Card
    {
        return Card::find($id);
    }

    public function forList(int $listId): Collection
    {
        return Card::where('board_list_id', $listId)->orderBy('position')->get();
    }

    public function create(array $attributes): Card
    {
        return Card::create($attributes);
    }

    public function update(Card $card, array $attributes): Card
    {
        $card->update($attributes);

        return $card;
    }

    public function delete(Card $card): void
    {
        $card->delete();
    }

    public function nextPosition(int $listId): int
    {
        $max = Card::where('board_list_id', $listId)->max('position');

        return $max === null ? 0 : $max + 1;
    }

    public function findMany(array $ids): Collection
    {
        return Card::with('list')->whereIn('id', $ids)->get();
    }

    public function reorderIntoList(int $listId, array $orderedCardIds): void
    {
        DB::transaction(function () use ($listId, $orderedCardIds) {
            foreach ($orderedCardIds as $position => $cardId) {
                Card::where('id', $cardId)->update([
                    'board_list_id' => $listId,
                    'position' => $position,
                ]);
            }
        });
    }

    public function attachLabel(Card $card, int $labelId): void
    {
        $card->labels()->syncWithoutDetaching([$labelId]);
    }

    public function detachLabel(Card $card, int $labelId): void
    {
        $card->labels()->detach($labelId);
    }

    public function labelsFor(int $cardId): Collection
    {
        return Card::findOrFail($cardId)->labels;
    }

    public function assignUser(Card $card, int $userId): void
    {
        $card->assignees()->syncWithoutDetaching([$userId]);
    }

    public function unassignUser(Card $card, int $userId): void
    {
        $card->assignees()->detach($userId);
    }

    public function assigneesFor(int $cardId): Collection
    {
        return Card::findOrFail($cardId)->assignees;
    }
}
