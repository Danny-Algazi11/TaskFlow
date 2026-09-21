<?php

namespace App\Application\BoardList\UseCases;

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\BoardList;
use Illuminate\Support\Collection;

class ListBoardListsUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureBoardIsAccessible,
        private readonly BoardListRepositoryInterface $lists,
    ) {
    }

    /**
     * @return Collection<int, BoardList>
     *
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $boardId, int $actingUserId): Collection
    {
        ($this->ensureBoardIsAccessible)($boardId, $actingUserId);

        return $this->lists->forBoard($boardId);
    }
}
