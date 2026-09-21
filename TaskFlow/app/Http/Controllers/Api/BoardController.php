<?php

namespace App\Http\Controllers\Api;

use App\Application\Board\DTOs\CreateBoardData;
use App\Application\Board\DTOs\UpdateBoardData;
use App\Application\Board\UseCases\CreateBoardUseCase;
use App\Application\Board\UseCases\DeleteBoardUseCase;
use App\Application\Board\UseCases\ListWorkspaceBoardsUseCase;
use App\Application\Board\UseCases\UpdateBoardUseCase;
use App\Application\Board\UseCases\ViewBoardUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\StoreBoardRequest;
use App\Http\Requests\Board\UpdateBoardRequest;
use App\Http\Resources\BoardResource;
use Illuminate\Http\Request;

/**
 * Routes are a shallow nested resource (workspaces.boards): index/store
 * carry {workspace} in the URL, show/update/destroy carry only {board}.
 * For those last three, the Use Case derives the owning workspace from
 * the board itself (see EnsureBoardIsAccessible) rather than trusting a
 * workspace id that isn't even present on those routes.
 *
 * Every action wraps its Resource in response()->json(...) explicitly —
 * see WorkspaceController's docblock for why that has to be consistent
 * across every action rather than left to Laravel's default.
 */
class BoardController extends Controller
{
    public function index(int $workspace, Request $request, ListWorkspaceBoardsUseCase $useCase)
    {
        return response()->json(BoardResource::collection($useCase->execute($workspace, $request->user()->id)));
    }

    public function store(int $workspace, StoreBoardRequest $request, CreateBoardUseCase $useCase)
    {
        $board = $useCase->execute(new CreateBoardData(
            workspaceId: $workspace,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
        ));

        return response()->json(new BoardResource($board), 201);
    }

    public function show(int $board, Request $request, ViewBoardUseCase $useCase)
    {
        return response()->json(new BoardResource($useCase->execute($board, $request->user()->id)));
    }

    public function update(int $board, UpdateBoardRequest $request, UpdateBoardUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateBoardData(
            boardId: $board,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
        ));

        return response()->json(new BoardResource($updated));
    }

    public function destroy(int $board, Request $request, DeleteBoardUseCase $useCase)
    {
        $useCase->execute($board, $request->user()->id);

        return response()->noContent();
    }
}
