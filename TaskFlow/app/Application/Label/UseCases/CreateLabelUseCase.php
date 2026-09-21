<?php

namespace App\Application\Label\UseCases;

use App\Application\Label\DTOs\CreateLabelData;
use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Label;

class CreateLabelUseCase
{
    public function __construct(
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
        private readonly LabelRepositoryInterface $labels,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateLabelData $data): Label
    {
        ($this->ensureWorkspaceIsAccessible)($data->workspaceId, $data->actingUserId);

        return $this->labels->create([
            'workspace_id' => $data->workspaceId,
            'name' => $data->name,
            'color' => $data->color,
        ]);
    }
}
