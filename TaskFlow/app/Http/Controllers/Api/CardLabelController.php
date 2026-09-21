<?php

namespace App\Http\Controllers\Api;

use App\Application\Card\UseCases\AttachLabelToCardUseCase;
use App\Application\Card\UseCases\DetachLabelFromCardUseCase;
use App\Application\Card\UseCases\ListCardLabelsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Resources\LabelResource;
use Illuminate\Http\Request;

class CardLabelController extends Controller
{
    public function index(int $card, Request $request, ListCardLabelsUseCase $useCase)
    {
        return response()->json(LabelResource::collection($useCase->execute($card, $request->user()->id)));
    }

    public function attach(int $card, int $label, Request $request, AttachLabelToCardUseCase $useCase)
    {
        $useCase->execute($card, $label, $request->user()->id);

        return response()->noContent();
    }

    public function detach(int $card, int $label, Request $request, DetachLabelFromCardUseCase $useCase)
    {
        $useCase->execute($card, $label, $request->user()->id);

        return response()->noContent();
    }
}
