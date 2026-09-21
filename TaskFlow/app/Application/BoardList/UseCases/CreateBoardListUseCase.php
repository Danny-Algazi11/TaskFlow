<?php

namespace App\Application\BoardList\UseCases;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Application\BoardList\DTOs\CreateBoardListData;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\BoardList;

class CreateBoardListUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureBoardIsAccessible,
        private readonly BoardListRepositoryInterface $lists,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateBoardListData $data): BoardList
    {
        ($this->ensureBoardIsAccessible)($data->boardId, $data->actingUserId);

        return $this->lists->create([
            'board_id' => $data->boardId,
            'name' => $data->name,
            'position' => $this->lists->nextPosition($data->boardId),
        ]);
    }
}
