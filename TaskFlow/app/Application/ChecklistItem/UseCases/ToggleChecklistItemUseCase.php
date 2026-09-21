<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\ChecklistItem\Services\EnsureChecklistItemIsAccessible;
use App\Domain\ChecklistItem\Exceptions\ChecklistItemNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\ChecklistItem;

/**
 * Checking an item off is its own action, not a special case of "update
 * the title" — a PATCH .../toggle reads better than asking a client to
 * resend the title just to flip one boolean, and it names the actual
 * business action instead of a generic field write.
 */
class ToggleChecklistItemUseCase
{
    public function __construct(
        private readonly EnsureChecklistItemIsAccessible $ensureAccessible,
        private readonly ChecklistItemRepositoryInterface $items,
    ) {
    }

    /**
     * @throws ChecklistItemNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $itemId, int $actingUserId): ChecklistItem
    {
        $item = ($this->ensureAccessible)($itemId, $actingUserId);

        return $this->items->update($item, ['is_complete' => ! $item->is_complete]);
    }
}
