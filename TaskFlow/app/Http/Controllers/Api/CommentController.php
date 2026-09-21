<?php

namespace App\Http\Controllers\Api;

use App\Application\Comment\DTOs\CreateCommentData;
use App\Application\Comment\UseCases\CreateCommentUseCase;
use App\Application\Comment\UseCases\DeleteCommentUseCase;
use App\Application\Comment\UseCases\ListCommentsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(int $card, Request $request, ListCommentsUseCase $useCase)
    {
        return response()->json(CommentResource::collection($useCase->execute($card, $request->user()->id)));
    }

    public function store(int $card, StoreCommentRequest $request, CreateCommentUseCase $useCase)
    {
        $comment = $useCase->execute(new CreateCommentData(
            cardId: $card,
            actingUserId: $request->user()->id,
            body: $request->string('body')->value(),
        ));

        return response()->json(new CommentResource($comment), 201);
    }

    public function destroy(int $comment, Request $request, DeleteCommentUseCase $useCase)
    {
        $useCase->execute($comment, $request->user()->id);

        return response()->noContent();
    }
}
