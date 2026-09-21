<?php

namespace App\Http\Controllers\Api;

use App\Application\ChecklistItem\DTOs\CreateChecklistItemData;
use App\Application\ChecklistItem\DTOs\ReorderChecklistItemsData;
use App\Application\ChecklistItem\DTOs\UpdateChecklistItemData;
use App\Application\ChecklistItem\UseCases\CreateChecklistItemUseCase;
use App\Application\ChecklistItem\UseCases\DeleteChecklistItemUseCase;
use App\Application\ChecklistItem\UseCases\ListChecklistItemsUseCase;
use App\Application\ChecklistItem\UseCases\ReorderChecklistItemsUseCase;
use App\Application\ChecklistItem\UseCases\ToggleChecklistItemUseCase;
use App\Application\ChecklistItem\UseCases\UpdateChecklistItemUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChecklistItem\ReorderChecklistItemsRequest;
use App\Http\Requests\ChecklistItem\StoreChecklistItemRequest;
use App\Http\Requests\ChecklistItem\UpdateChecklistItemRequest;
use App\Http\Resources\ChecklistItemResource;
use Illuminate\Http\Request;

class ChecklistItemController extends Controller
{
    public function index(int $card, Request $request, ListChecklistItemsUseCase $useCase)
    {
        return response()->json(ChecklistItemResource::collection($useCase->execute($card, $request->user()->id)));
    }

    public function store(int $card, StoreChecklistItemRequest $request, CreateChecklistItemUseCase $useCase)
    {
        $item = $useCase->execute(new CreateChecklistItemData(
            cardId: $card,
            actingUserId: $request->user()->id,
            title: $request->string('title')->value(),
        ));

        return response()->json(new ChecklistItemResource($item), 201);
    }

    public function update(int $item, UpdateChecklistItemRequest $request, UpdateChecklistItemUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateChecklistItemData(
            itemId: $item,
            actingUserId: $request->user()->id,
            title: $request->string('title')->value(),
        ));

        return response()->json(new ChecklistItemResource($updated));
    }

    public function toggle(int $item, Request $request, ToggleChecklistItemUseCase $useCase)
    {
        return response()->json(new ChecklistItemResource($useCase->execute($item, $request->user()->id)));
    }

    public function destroy(int $item, Request $request, DeleteChecklistItemUseCase $useCase)
    {
        $useCase->execute($item, $request->user()->id);

        return response()->noContent();
    }

    public function reorder(int $card, ReorderChecklistItemsRequest $request, ReorderChecklistItemsUseCase $useCase)
    {
        $useCase->execute(new ReorderChecklistItemsData(
            cardId: $card,
            actingUserId: $request->user()->id,
            orderedItemIds: $request->input('item_ids'),
        ));

        return response()->noContent();
    }
}
