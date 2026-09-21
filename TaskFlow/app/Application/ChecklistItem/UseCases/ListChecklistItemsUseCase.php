<?php

namespace App\Application\ChecklistItem\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\ChecklistItem;
use Illuminate\Support\Collection;

class ListChecklistItemsUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly ChecklistItemRepositoryInterface $items,
    ) {
    }

    /**
     * @return Collection<int, ChecklistItem>
     *
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $cardId, int $actingUserId): Collection
    {
        ($this->ensureCardIsAccessible)($cardId, $actingUserId);

        return $this->items->forCard($cardId);
    }
}
