<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Infrastructure\Persistence\Models\Comment;
use Illuminate\Support\Collection;

class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function find(int $id): ?Comment
    {
        return Comment::find($id);
    }

    public function forCard(int $cardId): Collection
    {
        return Comment::where('card_id', $cardId)->latest()->get();
    }

    public function create(array $attributes): Comment
    {
        return Comment::create($attributes);
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
