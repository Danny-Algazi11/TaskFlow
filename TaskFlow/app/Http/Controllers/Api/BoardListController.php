<?php

namespace App\Http\Controllers\Api;

use App\Application\BoardList\DTOs\CreateBoardListData;
use App\Application\BoardList\DTOs\ReorderBoardListsData;
use App\Application\BoardList\DTOs\UpdateBoardListData;
use App\Application\BoardList\UseCases\CreateBoardListUseCase;
use App\Application\BoardList\UseCases\DeleteBoardListUseCase;
use App\Application\BoardList\UseCases\ListBoardListsUseCase;
use App\Application\BoardList\UseCases\ReorderBoardListsUseCase;
use App\Application\BoardList\UseCases\UpdateBoardListUseCase;
use App\Application\BoardList\UseCases\ViewBoardListUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\BoardList\ReorderBoardListsRequest;
use App\Http\Requests\BoardList\StoreBoardListRequest;
use App\Http\Requests\BoardList\UpdateBoardListRequest;
use App\Http\Resources\BoardListResource;
use Illuminate\Http\Request;

/**
 * Same shallow-nesting and no-route-model-binding conventions as
 * BoardController: index/store carry {board}, show/update/destroy carry
 * only {list}, and every id is looked up inside a Use Case.
 */
class BoardListController extends Controller
{
    public function index(int $board, Request $request, ListBoardListsUseCase $useCase)
    {
        return response()->json(BoardListResource::collection($useCase->execute($board, $request->user()->id)));
    }

    public function store(int $board, StoreBoardListRequest $request, CreateBoardListUseCase $useCase)
    {
        $list = $useCase->execute(new CreateBoardListData(
            boardId: $board,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
        ));

        return response()->json(new BoardListResource($list), 201);
    }

    public function show(int $list, Request $request, ViewBoardListUseCase $useCase)
    {
        return response()->json(new BoardListResource($useCase->execute($list, $request->user()->id)));
    }

    public function update(int $list, UpdateBoardListRequest $request, UpdateBoardListUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateBoardListData(
            listId: $list,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
        ));

        return response()->json(new BoardListResource($updated));
    }

    public function destroy(int $list, Request $request, DeleteBoardListUseCase $useCase)
    {
        $useCase->execute($list, $request->user()->id);

        return response()->noContent();
    }

    public function reorder(int $board, ReorderBoardListsRequest $request, ReorderBoardListsUseCase $useCase)
    {
        $useCase->execute(new ReorderBoardListsData(
            boardId: $board,
            actingUserId: $request->user()->id,
            orderedListIds: $request->input('list_ids'),
        ));

        return response()->noContent();
    }
}
