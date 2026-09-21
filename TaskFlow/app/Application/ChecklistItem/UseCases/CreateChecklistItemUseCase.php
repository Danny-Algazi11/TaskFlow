<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Application\ChecklistItem\DTOs\CreateChecklistItemData;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\ChecklistItem;

class CreateChecklistItemUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly ChecklistItemRepositoryInterface $items,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateChecklistItemData $data): ChecklistItem
    {
        ($this->ensureCardIsAccessible)($data->cardId, $data->actingUserId);

        return $this->items->create([
            'card_id' => $data->cardId,
            'title' => $data->title,
            'is_complete' => false,
            'position' => $this->items->nextPosition($data->cardId),
        ]);
    }
}
