<?php

namespace App\Application\Label\UseCases;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Label;
use Illuminate\Support\Collection;

class ListWorkspaceLabelsUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly LabelRepositoryInterface $labels,
    ) {
    }

    /**
     * @return Collection<int, Label>
     *
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $workspaceId, int $actingUserId): Collection
    {
        ($this->ensureWorkspaceIsAccessible)($workspaceId, $actingUserId);

        return $this->labels->forWorkspace($workspaceId);
    }
}
