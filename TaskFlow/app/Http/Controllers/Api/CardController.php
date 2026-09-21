<?php

namespace App\Http\Controllers\Api;

use App\Application\Card\DTOs\CreateCardData;
use App\Application\Card\DTOs\ReorderCardsData;
use App\Application\Card\DTOs\UpdateCardData;
use App\Application\Card\UseCases\CreateCardUseCase;
use App\Application\Card\UseCases\DeleteCardUseCase;
use App\Application\Card\UseCases\ListCardsUseCase;
use App\Application\Card\UseCases\ReorderCardsUseCase;
use App\Application\Card\UseCases\UpdateCardUseCase;
use App\Application\Card\UseCases\ViewCardUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Card\ReorderCardsRequest;
use App\Http\Requests\Card\StoreCardRequest;
use App\Http\Requests\Card\UpdateCardRequest;
use App\Http\Resources\CardResource;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(int $list, Request $request, ListCardsUseCase $useCase)
    {
        return response()->json(CardResource::collection($useCase->execute($list, $request->user()->id)));
    }

    public function store(int $list, StoreCardRequest $request, CreateCardUseCase $useCase)
    {
        $card = $useCase->execute(new CreateCardData(
            listId: $list,
            actingUserId: $request->user()->id,
            title: $request->string('title')->value(),
            description: $request->input('description'),
            dueDate: $request->input('due_date'),
        ));

        return response()->json(new CardResource($card), 201);
    }

    public function show(int $card, Request $request, ViewCardUseCase $useCase)
    {
        return response()->json(new CardResource($useCase->execute($card, $request->user()->id)));
    }

    public function update(int $card, UpdateCardRequest $request, UpdateCardUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateCardData(
            cardId: $card,
            actingUserId: $request->user()->id,
            title: $request->string('title')->value(),
            description: $request->input('description'),
            dueDate: $request->input('due_date'),
        ));

        return response()->json(new CardResource($updated));
    }

    public function destroy(int $card, Request $request, DeleteCardUseCase $useCase)
    {
        $useCase->execute($card, $request->user()->id);

        return response()->noContent();
    }

    /**
     * $list here is the DESTINATION list — see ReorderCardsUseCase for why
     * one endpoint handles both a same-list reorder and a cross-list drag.
     */
    public function reorder(int $list, ReorderCardsRequest $request, ReorderCardsUseCase $useCase)
    {
        $useCase->execute(new ReorderCardsData(
            listId: $list,
            actingUserId: $request->user()->id,
            orderedCardIds: $request->input('card_ids'),
        ));

        return response()->noContent();
    }
}
