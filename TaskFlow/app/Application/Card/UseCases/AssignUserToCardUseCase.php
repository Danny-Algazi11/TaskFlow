<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\AssigneeNotWorkspaceMemberException;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;

class AssignUserToCardUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly WorkspaceRepositoryInterface $workspaces,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws AssigneeNotWorkspaceMemberException
     */
    public function execute(int $cardId, int $userId, int $actingUserId): void
    {
        $card = ($this->ensureCardIsAccessible)($cardId, $actingUserId);

        $workspace = $this->workspaces->find($card->list->board->workspace_id);

        if (! $workspace || ! $this->workspaces->isMember($workspace, $userId)) {
            throw new AssigneeNotWorkspaceMemberException();
        }

        $this->cards->assignUser($card, $userId);
    }
}
