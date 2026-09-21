<?php

namespace App\Application\Label\Services;

use App\Application\Workspace\Services\EnsureWorkspaceIsAccessible;
use App\Domain\Label\Exceptions\LabelNotFoundException;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Domain\Workspace\Exceptions\WorkspaceNotFoundException;
use App\Infrastructure\Persistence\Models\Label;

class EnsureLabelIsAccessible
{
    public function __construct(
        private readonly LabelRepositoryInterface $labels,
        private readonly EnsureWorkspaceIsAccessible $ensureWorkspaceIsAccessible,
    ) {
    }

    /**
     * @throws LabelNotFoundException
     * @throws WorkspaceNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function __invoke(int $labelId, int $actingUserId): Label
    {
        $label = $this->labels->find($labelId);

        if (! $label) {
            throw new LabelNotFoundException();
        }

        ($this->ensureWorkspaceIsAccessible)($label->workspace_id, $actingUserId);

        return $label;
    }
}
