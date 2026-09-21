<?php

namespace App\Domain\Comment\Repositories;

use App\Infrastructure\Persistence\Models\Comment;
use Illuminate\Support\Collection;

interface CommentRepositoryInterface
{
    public function find(int $id): ?Comment;

    /**
     * @return Collection<int, Comment> newest first
     */
    public function forCard(int $cardId): Collection;

    public function create(array $attributes): Comment;

    public function delete(Comment $comment): void;
}
