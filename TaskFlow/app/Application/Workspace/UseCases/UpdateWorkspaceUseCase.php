<?php

namespace App\Application\Workspace\UseCases;

use App\Application\Workspace\DTOs\UpdateWorkspaceData;
use App\Application\Workspace\Services\EnsureActingUserIsWorkspaceAdmin;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;

class UpdateWorkspaceUseCase
{
    public function __construct(
        private readonly EnsureActingUserIsWorkspaceAdmin $ensureIsAdmin,
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(UpdateWorkspaceData $data): Workspace
    {
        $workspace = ($this->ensureIsAdmin)($data->workspaceId, $data->actingUserId);

        return $this->workspaces->update($workspace, ['name' => $data->name]);
    }
}
