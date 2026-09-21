<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\ChecklistItem\DTOs\UpdateChecklistItemData;
use App\Application\ChecklistItem\Services\EnsureChecklistItemIsAccessible;
use App\Domain\ChecklistItem\Exceptions\ChecklistItemNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\ChecklistItem;

class UpdateChecklistItemUseCase
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
    public function execute(UpdateChecklistItemData $data): ChecklistItem
    {
        $item = ($this->ensureAccessible)($data->itemId, $data->actingUserId);

        return $this->items->update($item, ['title' => $data->title]);
    }
}
