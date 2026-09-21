<?php

namespace App\Http\Controllers\Api;

use App\Application\WorkspaceInvitation\DTOs\AcceptInvitationData;
use App\Application\WorkspaceInvitation\DTOs\CreateInvitationData;
use App\Application\WorkspaceInvitation\UseCases\AcceptInvitationUseCase;
use App\Application\WorkspaceInvitation\UseCases\InviteToWorkspaceUseCase;
use App\Application\WorkspaceInvitation\UseCases\ListWorkspaceInvitationsUseCase;
use App\Application\WorkspaceInvitation\UseCases\RevokeInvitationUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorkspaceInvitation\StoreInvitationRequest;
use App\Http\Resources\WorkspaceInvitationResource;
use App\Http\Resources\WorkspaceResource;
use Illuminate\Http\Request;

class WorkspaceInvitationController extends Controller
{
    public function index(int $workspace, Request $request, ListWorkspaceInvitationsUseCase $useCase)
    {
        return response()->json(
            WorkspaceInvitationResource::collection($useCase->execute($workspace, $request->user()->id))
        );
    }

    public function store(int $workspace, StoreInvitationRequest $request, InviteToWorkspaceUseCase $useCase)
    {
        $invitation = $useCase->execute(new CreateInvitationData(
            workspaceId: $workspace,
            actingUserId: $request->user()->id,
            email: $request->string('email')->value(),
            role: $request->string('role')->value(),
        ));

        // The one place the raw token is ever exposed — see
        // WorkspaceInvitationResource's docblock. Built as a plain array
        // rather than Resource::additional(), which only merges through
        // toResponse() — the pathway this codebase's response()->json()
        // convention deliberately bypasses (see WorkspaceController's
        // docblock for why every action here is consistent about that).
        return response()->json([
            ...(new WorkspaceInvitationResource($invitation))->toArray($request),
            'token' => $invitation->token,
        ], 201);
    }

    public function destroy(int $invitation, Request $request, RevokeInvitationUseCase $useCase)
    {
        $useCase->execute($invitation, $request->user()->id);

        return response()->noContent();
    }

    public function accept(string $token, Request $request, AcceptInvitationUseCase $useCase)
    {
        $workspace = $useCase->execute(new AcceptInvitationData(
            token: $token,
            actingUserId: $request->user()->id,
            actingUserEmail: $request->user()->email,
        ));

        return response()->json(new WorkspaceResource($workspace));
    }
}
