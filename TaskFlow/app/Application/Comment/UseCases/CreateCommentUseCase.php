<?php

namespace App\Application\Comment\UseCases;

use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Application\Comment\DTOs\CreateCommentData;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Persistence\Models\Comment;

class CreateCommentUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureCardIsAccessible,
        private readonly CommentRepositoryInterface $comments,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateCommentData $data): Comment
    {
        ($this->ensureCardIsAccessible)($data->cardId, $data->actingUserId);

        return $this->comments->create([
            'card_id' => $data->cardId,
            'user_id' => $data->actingUserId,
            'body' => $data->body,
        ]);
    }
}
