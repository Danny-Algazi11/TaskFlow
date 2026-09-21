<?php

namespace App\Application\Workspace\UseCases;

use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;
use Illuminate\Support\Collection;

class ListUserWorkspacesUseCase
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @return Collection<int, Workspace>
     */
    public function execute(int $userId): Collection
    {
        return $this->workspaces->forUser($userId);
    }
}
