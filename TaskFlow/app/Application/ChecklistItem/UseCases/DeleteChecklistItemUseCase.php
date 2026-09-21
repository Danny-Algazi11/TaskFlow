<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\ChecklistItem\Services\EnsureChecklistItemIsAccessible;
use App\Domain\ChecklistItem\Exceptions\ChecklistItemNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

class DeleteChecklistItemUseCase
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
    public function execute(int $itemId, int $actingUserId): void
    {
        $item = ($this->ensureAccessible)($itemId, $actingUserId);

        $this->items->delete($item);
    }
}
