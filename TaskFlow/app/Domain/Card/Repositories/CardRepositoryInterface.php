<?php

namespace App\Domain\Card\Repositories;

use App\Infrastructure\Persistence\Models\Card;
use Illuminate\Support\Collection;

interface CardRepositoryInterface
{
    public function find(int $id): ?Card;

    /**
     * @return Collection<int, Card> ordered by position
     */
    public function forList(int $listId): Collection;

    public function create(array $attributes): Card;

    public function update(Card $card, array $attributes): Card;

    public function delete(Card $card): void;

    public function nextPosition(int $listId): int;

    /**
     * @param int[] $ids
     * @return Collection<int, Card> matching cards found, each with its
     *                                `list` relation eager-loaded
     */
    public function findMany(array $ids): Collection;

    /**
     * Move every card in $orderedCardIds into $listId, positioned by its
     * index in the array. This is the one operation that handles both a
     * same-list reorder and a cross-list drag — a card's board_list_id
     * just gets overwritten to $listId either way. Cards not mentioned are
     * left untouched, including ones already in $listId that got omitted
     * from the array; nothing here removes a card from a list.
     */
    public function reorderIntoList(int $listId, array $orderedCardIds): void;

    /**
     * Pivot management for a card's own relations — kept on this interface
     * rather than split into separate repositories, the same way
     * WorkspaceRepositoryInterface owns its own membership pivot methods.
     */
    public function attachLabel(Card $card, int $labelId): void;

    public function detachLabel(Card $card, int $labelId): void;

    /**
     * @return Collection<int, \App\Infrastructure\Persistence\Models\Label>
     */
    public function labelsFor(int $cardId): Collection;

    public function assignUser(Card $card, int $userId): void;

    public function unassignUser(Card $card, int $userId): void;

    /**
     * @return Collection<int, \App\Infrastructure\Persistence\Models\User>
     */
    public function assigneesFor(int $cardId): Collection;
}
