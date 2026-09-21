<?php

namespace App\Http\Controllers\Api;

use App\Application\Label\DTOs\CreateLabelData;
use App\Application\Label\DTOs\UpdateLabelData;
use App\Application\Label\UseCases\CreateLabelUseCase;
use App\Application\Label\UseCases\DeleteLabelUseCase;
use App\Application\Label\UseCases\ListWorkspaceLabelsUseCase;
use App\Application\Label\UseCases\UpdateLabelUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Label\StoreLabelRequest;
use App\Http\Requests\Label\UpdateLabelRequest;
use App\Http\Resources\LabelResource;
use Illuminate\Http\Request;

class LabelController extends Controller
{
    public function index(int $workspace, Request $request, ListWorkspaceLabelsUseCase $useCase)
    {
        return response()->json(LabelResource::collection($useCase->execute($workspace, $request->user()->id)));
    }

    public function store(int $workspace, StoreLabelRequest $request, CreateLabelUseCase $useCase)
    {
        $label = $useCase->execute(new CreateLabelData(
            workspaceId: $workspace,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
            color: $request->string('color')->value(),
        ));

        return response()->json(new LabelResource($label), 201);
    }

    public function update(int $label, UpdateLabelRequest $request, UpdateLabelUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateLabelData(
            labelId: $label,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
            color: $request->string('color')->value(),
        ));

        return response()->json(new LabelResource($updated));
    }

    public function destroy(int $label, Request $request, DeleteLabelUseCase $useCase)
    {
        $useCase->execute($label, $request->user()->id);

        return response()->noContent();
    }
}
