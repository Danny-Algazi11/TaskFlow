<?php

namespace App\Application\Board\UseCases;

use App\Application\Board\DTOs\UpdateBoardData;
use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\Board\Exceptions\BoardNotFoundException;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Board;

class UpdateBoardUseCase
{
    public function __construct(
        private readonly EnsureBoardIsAccessible $ensureAccessible,
        private readonly BoardRepositoryInterface $boards,
    ) {
    }

    /**
     * @throws BoardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(UpdateBoardData $data): Board
    {
        $board = ($this->ensureAccessible)($data->boardId, $data->actingUserId);

        return $this->boards->update($board, ['name' => $data->name]);
    }
}
