<?php

namespace App\Application\BoardList\UseCases;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Application\BoardList\DTOs\ReorderBoardListsData;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\BoardList\Exceptions\InvalidListReorderException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

/**
 * A board's lists are a closed set — dragging a column can only reorder
 * the columns already on that board, never add or remove one. So the
 * given ids must be *exactly* the board's current list ids, just in a
 * new order: same set, different sequence. That's what the array_diff
 * checks below enforce, in both directions.
 */
class ReorderBoardListsUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureBoardIsAccessible,
        private readonly BoardListRepositoryInterface $lists,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws InvalidListReorderException
     */
    public function execute(ReorderBoardListsData $data): void
    {
        ($this->ensureBoardIsAccessible)($data->boardId, $data->actingUserId);

        $currentIds = $this->lists->forBoard($data->boardId)->pluck('id')->all();

        if (array_diff($currentIds, $data->orderedListIds) || array_diff($data->orderedListIds, $currentIds)) {
            throw new InvalidListReorderException();
        }

        $this->lists->reorder($data->boardId, $data->orderedListIds);
    }
}
