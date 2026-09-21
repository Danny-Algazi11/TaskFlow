<?php

namespace App\Http\Controllers\Api;

use App\Application\Workspace\DTOs\CreateWorkspaceData;
use App\Application\Workspace\DTOs\UpdateWorkspaceData;
use App\Application\Workspace\UseCases\CreateWorkspaceUseCase;
use App\Application\Workspace\UseCases\DeleteWorkspaceUseCase;
use App\Application\Workspace\UseCases\ListUserWorkspacesUseCase;
use App\Application\Workspace\UseCases\UpdateWorkspaceUseCase;
use App\Application\Workspace\UseCases\ViewWorkspaceUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workspace\StoreWorkspaceRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceRequest;
use App\Http\Resources\WorkspaceResource;
use Illuminate\Http\Request;

/**
 * No route-model-binding here on purpose: {workspace} arrives as a raw id,
 * and the Use Case does the lookup. That's what lets "not found" and
 * "not a member" be OUR exceptions (mapped centrally in bootstrap/app.php)
 * instead of Laravel's generic 404, which wouldn't know about membership.
 *
 * Every action wraps its Resource in response()->json(...) explicitly.
 * Returning a Resource directly from a controller lets Laravel's own
 * response pipeline wrap it in {"data": ...} automatically; passing it
 * through response()->json() instead does not. Picking one and using it
 * everywhere avoids an API where some endpoints are wrapped and others
 * aren't for no reason a client could guess.
 */
class WorkspaceController extends Controller
{
    public function index(Request $request, ListUserWorkspacesUseCase $useCase)
    {
        return response()->json(WorkspaceResource::collection($useCase->execute($request->user()->id)));
    }

    public function store(StoreWorkspaceRequest $request, CreateWorkspaceUseCase $useCase)
    {
        $workspace = $useCase->execute(new CreateWorkspaceData(
            name: $request->string('name')->value(),
            ownerId: $request->user()->id,
        ));

        return response()->json(new WorkspaceResource($workspace), 201);
    }

    public function show(int $workspace, Request $request, ViewWorkspaceUseCase $useCase)
    {
        return response()->json(new WorkspaceResource($useCase->execute($workspace, $request->user()->id)));
    }

    public function update(int $workspace, UpdateWorkspaceRequest $request, UpdateWorkspaceUseCase $useCase)
    {
        $updated = $useCase->execute(new UpdateWorkspaceData(
            workspaceId: $workspace,
            actingUserId: $request->user()->id,
            name: $request->string('name')->value(),
        ));

        return response()->json(new WorkspaceResource($updated));
    }

    public function destroy(int $workspace, Request $request, DeleteWorkspaceUseCase $useCase)
    {
        $useCase->execute($workspace, $request->user()->id);

        return response()->noContent();
    }
}
