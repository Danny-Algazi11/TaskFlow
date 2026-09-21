<?php

namespace App\Application\Label\UseCases;

use App\Application\Label\DTOs\UpdateLabelData;
use App\Application\Label\Services\EnsureLabelIsAccessible;
use App\Domain\Label\Exceptions\LabelNotFoundException;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Label;

class UpdateLabelUseCase
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
    public function execute(UpdateLabelData $data): Label
    {
        $label = ($this->ensureAccessible)($data->labelId, $data->actingUserId);

        return $this->labels->update($label, [
            'name' => $data->name,
            'color' => $data->color,
        ]);
    }
}
