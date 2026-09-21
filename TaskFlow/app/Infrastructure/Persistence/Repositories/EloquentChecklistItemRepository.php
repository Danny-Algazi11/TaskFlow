<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Infrastructure\Persistence\Models\ChecklistItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentChecklistItemRepository implements ChecklistItemRepositoryInterface
{
    public function find(int $id): ?ChecklistItem
    {
        return ChecklistItem::find($id);
    }

    public function forCard(int $cardId): Collection
    {
        return ChecklistItem::where('card_id', $cardId)->orderBy('position')->get();
    }

    public function create(array $attributes): ChecklistItem
    {
        return ChecklistItem::create($attributes);
    }

    public function update(ChecklistItem $item, array $attributes): ChecklistItem
    {
        $item->update($attributes);

        return $item;
    }

    public function delete(ChecklistItem $item): void
    {
        $item->delete();
    }

    public function nextPosition(int $cardId): int
    {
        $max = ChecklistItem::where('card_id', $cardId)->max('position');

        return $max === null ? 0 : $max + 1;
    }

    public function reorder(int $cardId, array $orderedItemIds): void
    {
        DB::transaction(function () use ($orderedItemIds) {
            foreach ($orderedItemIds as $position => $itemId) {
                ChecklistItem::where('id', $itemId)->update(['position' => $position]);
            }
        });
    }
}
