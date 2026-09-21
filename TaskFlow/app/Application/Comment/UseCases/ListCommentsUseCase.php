<?php

namespace App\Application\Comment\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Comment;
use Illuminate\Support\Collection;

class ListCommentsUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly CommentRepositoryInterface $comments,
    ) {
    }

    /**
     * @return Collection<int, Comment>
     *
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(int $cardId, int $actingUserId): Collection
    {
        ($this->ensureCardIsAccessible)($cardId, $actingUserId);

        return $this->comments->forCard($cardId);
    }
}
