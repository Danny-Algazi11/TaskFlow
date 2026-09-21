<?php

namespace App\Application\Comment\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Comment\Exceptions\CommentAuthorMismatchException;
use App\Domain\Comment\Exceptions\CommentNotFoundException;
use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;

class DeleteCommentUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly CommentRepositoryInterface $comments,
    ) {
    }

    /**
     * @throws CommentNotFoundException
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws CommentAuthorMismatchException
     */
    public function execute(int $commentId, int $actingUserId): void
    {
        $comment = $this->comments->find($commentId);

        if (! $comment) {
            throw new CommentNotFoundException();
        }

        // Confirms the acting user can at least see the card before
        // leaking anything about the comment's existence back to them.
        ($this->ensureCardIsAccessible)($comment->card_id, $actingUserId);

        if ((int) $comment->user_id !== $actingUserId) {
            throw new CommentAuthorMismatchException();
        }

        $this->comments->delete($comment);
    }
}
