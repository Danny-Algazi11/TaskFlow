<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Label\Exceptions\LabelNotFoundException;
use App\Domain\Label\Exceptions\LabelWorkspaceMismatchException;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

class AttachLabelToCardUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly LabelRepositoryInterface $labels,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws LabelNotFoundException
     * @throws LabelWorkspaceMismatchException
     */
    public function execute(int $cardId, int $labelId, int $actingUserId): void
    {
        $card = ($this->ensureCardIsAccessible)($cardId, $actingUserId);

        $label = $this->labels->find($labelId);

        if (! $label) {
            throw new LabelNotFoundException();
        }

        if ((int) $label->workspace_id !== (int) $card->list->board->workspace_id) {
            throw new LabelWorkspaceMismatchException();
        }

        $this->cards->attachLabel($card, $labelId);
    }
}
