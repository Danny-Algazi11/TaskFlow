<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Label;
use Illuminate\Support\Collection;

class ListCardLabelsUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @return Collection<int, Label>
     *
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $cardId, int $actingUserId): Collection
    {
        ($this->ensureCardIsAccessible)($cardId, $actingUserId);

        return $this->cards->labelsFor($cardId);
    }
}
