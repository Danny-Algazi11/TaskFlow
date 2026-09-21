<?php

namespace App\Application\ChecklistItem\Services;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\ChecklistItem\Exceptions\ChecklistItemNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\ChecklistItem;

class EnsureChecklistItemIsAccessible
{
    public function __construct(
        private readonly ChecklistItemRepositoryInterface $items,
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
    ) {
    }

    /**
     * @throws ChecklistItemNotFoundException
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $itemId, int $actingUserId): ChecklistItem
    {
        $item = $this->items->find($itemId);

        if (! $item) {
            throw new ChecklistItemNotFoundException();
        }

        ($this->ensureCardIsAccessible)($item->card_id, $actingUserId);

        return $item;
    }
}
