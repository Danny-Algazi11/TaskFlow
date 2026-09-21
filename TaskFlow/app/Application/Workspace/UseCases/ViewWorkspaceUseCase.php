<?php

namespace App\Application\Workspace\UseCases;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Workspace;

class ViewWorkspaceUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureAccessible,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): Workspace
    {
        return ($this->ensureAccessible)($workspaceId, $actingUserId);
    }
}
