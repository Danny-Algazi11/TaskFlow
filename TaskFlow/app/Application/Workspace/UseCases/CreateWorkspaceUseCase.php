<?php

namespace App\Application\Workspace\UseCases;

use App\Application\Workspace\DTOs\CreateWorkspaceData;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workspace;

/**
 * "Creating a workspace makes you its admin" is the business rule here —
 * two writes (the workspace row, the membership row) that must happen
 * together so a creator is never left unable to manage their own
 * workspace. That invariant is exactly why this is a Use Case and not
 * just a repository create() called straight from the controller.
 */
class CreateWorkspaceUseCase
{
    public function __construct(
        private readonly WorkspaceRepositoryInterface $workspaces,
    ) {
    }

    public function execute(CreateWorkspaceData $data): Workspace
    {
        $workspace = $this->workspaces->create([
            'name' => $data->name,
            'owner_id' => $data->ownerId,
        ]);

        $this->workspaces->addMember($workspace, $data->ownerId, 'admin');

        return $workspace;
    }
}
