<?php

namespace App\Http\Controllers\Api;

use App\Application\Card\UseCases\AssignUserToCardUseCase;
use App\Application\Card\UseCases\ListCardAssigneesUseCase;
use App\Application\Card\UseCases\UnassignUserFromCardUseCase;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class CardAssigneeController extends Controller
{
    public function index(int $card, Request $request, ListCardAssigneesUseCase $useCase)
    {
        return response()->json(UserResource::collection($useCase->execute($card, $request->user()->id)));
    }

    public function assign(int $card, int $user, Request $request, AssignUserToCardUseCase $useCase)
    {
        $useCase->execute($card, $user, $request->user()->id);

        return response()->noContent();
    }

    public function unassign(int $card, int $user, Request $request, UnassignUserFromCardUseCase $useCase)
    {
        $useCase->execute($card, $user, $request->user()->id);

        return response()->noContent();
    }
}
