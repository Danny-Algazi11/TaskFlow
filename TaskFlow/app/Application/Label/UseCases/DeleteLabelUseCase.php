<?php

namespace App\Application\Label\UseCases;

use App\Application\Label\Services\EnsureLabelIsAccessible;
use App\Domain\Label\Exceptions\LabelNotFoundException;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

/**
 * Deleting a label cascades off any card it's attached to (card_label's
 * foreign key is cascadeOnDelete) — no application-level cleanup needed.
 */
class DeleteLabelUseCase
{
    public function __construct(
        private readonly EnsureLabelIsAccessible $ensureAccessible,
        private readonly LabelRepositoryInterface $labels,
    ) {
    }

    /**
     * @throws LabelNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $labelId, int $actingUserId): void
    {
        $label = ($this->ensureAccessible)($labelId, $actingUserId);

        $this->labels->delete($label);
    }
}
