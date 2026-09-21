<?php

namespace App\Application\Board\Services;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Board;

/**
 * Shared by every Use Case that acts on one existing board (view, update,
 * delete): find it, then confirm the acting user belongs to the workspace
 * that owns it. Worth extracting because it's the *same* check in all
 * three places — unlike Workspace's view/update/delete rules, which are
 * three genuinely different rules (membership / role / ownership) that
 * only happen to look similar.
 */
class EnsureBoardIsAccessible
{
    public function __construct(
        private readonly BoardRepositoryInterface $boards,
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $boardId, int $actingUserId): Board
    {
        $board = $this->boards->find($boardId);

        if (! $board) {
            throw new BoardNotFoundException();
        }

        ($this->ensureWorkspaceIsAccessible)($board->workspace_id, $actingUserId);

        return $board;
    }
}
