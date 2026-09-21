<?php

namespace App\Http\Controllers\Api;

use App\Application\Workspace\UseCases\ListWorkspaceMembersUseCase;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkspaceMemberResource;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(int $workspace, Request $request, ListWorkspaceMembersUseCase $useCase)
    {
        return response()->json(
            WorkspaceMemberResource::collection($useCase->execute($workspace, $request->user()->id))
        );
    }
}
