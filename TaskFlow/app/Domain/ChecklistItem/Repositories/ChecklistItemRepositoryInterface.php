<?php

namespace App\Domain\ChecklistItem\Repositories;

use App\Infrastructure\Persistence\Models\ChecklistItem;
use Illuminate\Support\Collection;

interface ChecklistItemRepositoryInterface
{
    public function find(int $id): ?ChecklistItem;

    /**
     * @return Collection<int, ChecklistItem> ordered by position
     */
    public function forCard(int $cardId): Collection;

    public function create(array $attributes): ChecklistItem;

    public function update(ChecklistItem $item, array $attributes): ChecklistItem;

    public function delete(ChecklistItem $item): void;

    public function nextPosition(int $cardId): int;

    /**
     * Rewrite every item's position to match its index in $orderedItemIds.
     * Callers validate the id set is exactly this card's current items —
     * unlike Card's own reorder, a checklist item never moves to a
     * different card, so this is a closed-set reorder like BoardList's.
     */
    public function reorder(int $cardId, array $orderedItemIds): void;
}
