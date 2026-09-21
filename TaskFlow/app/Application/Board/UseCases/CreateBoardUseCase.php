<?php

namespace App\Application\Board\UseCases;

use App\Application\Board\DTOs\CreateBoardData;
use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Board;

/**
 * Any workspace member can create a board — there's no separate "board
 * owner" concept yet, just the workspace's existing admin/member roles.
 * Simplest rule that satisfies the current phase; revisit if a feature
 * ever needs finer-grained board permissions.
 */
class CreateBoardUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly BoardRepositoryInterface $boards,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateBoardData $data): Board
    {
        ($this->ensureWorkspaceIsAccessible)($data->workspaceId, $data->actingUserId);

        return $this->boards->create([
            'workspace_id' => $data->workspaceId,
            'created_by' => $data->actingUserId,
            'name' => $data->name,
        ]);
    }
}
