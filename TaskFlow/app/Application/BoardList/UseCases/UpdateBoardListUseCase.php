<?php

namespace App\Application\BoardList\UseCases;

use App\Application\BoardList\DTOs\UpdateBoardListData;
use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\BoardList;

class UpdateBoardListUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureAccessible,
        private readonly BoardListRepositoryInterface $lists,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(UpdateBoardListData $data): BoardList
    {
        $list = ($this->ensureAccessible)($data->listId, $data->actingUserId);

        return $this->lists->update($list, ['name' => $data->name]);
    }
}
