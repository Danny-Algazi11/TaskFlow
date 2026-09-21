<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Application\ChecklistItem\DTOs\ReorderChecklistItemsData;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\ChecklistItem\Exceptions\InvalidChecklistItemReorderException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

/**
 * Closed-set reorder, like ReorderBoardListsUseCase — a checklist item
 * never moves to a different card, so the given ids must be exactly this
 * card's current items in a new order.
 */
class ReorderChecklistItemsUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly ChecklistItemRepositoryInterface $items,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws InvalidChecklistItemReorderException
     */
    public function execute(ReorderChecklistItemsData $data): void
    {
        ($this->ensureCardIsAccessible)($data->cardId, $data->actingUserId);

        $currentIds = $this->items->forCard($data->cardId)->pluck('id')->all();

        if (array_diff($currentIds, $data->orderedItemIds) || array_diff($data->orderedItemIds, $currentIds)) {
            throw new InvalidChecklistItemReorderException();
        }

        $this->items->reorder($data->cardId, $data->orderedItemIds);
    }
}
