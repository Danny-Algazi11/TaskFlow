<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\BoardListController;
use App\Http\Controllers\Api\CardAssigneeController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CardLabelController;
use App\Http\Controllers\Api\ChecklistItemController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\WorkspaceInvitationController;
use App\Http\Controllers\Api\WorkspaceMemberController;



Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('workspaces', WorkspaceController::class);
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index']);
    Route::apiResource('workspaces.boards', BoardController::class)->shallow();

    Route::patch('boards/{board}/lists/reorder', [BoardListController::class, 'reorder']);
    Route::apiResource('boards.lists', BoardListController::class)->shallow();

    Route::patch('lists/{list}/cards/reorder', [CardController::class, 'reorder']);
    Route::apiResource('lists.cards', CardController::class)->shallow();

    Route::apiResource('workspaces.labels', LabelController::class)->shallow()->except(['show']);

    Route::get('cards/{card}/labels', [CardLabelController::class, 'index']);
    Route::post('cards/{card}/labels/{label}', [CardLabelController::class, 'attach']);
    Route::delete('cards/{card}/labels/{label}', [CardLabelController::class, 'detach']);

    Route::get('cards/{card}/assignees', [CardAssigneeController::class, 'index']);
    Route::post('cards/{card}/assignees/{user}', [CardAssigneeController::class, 'assign']);
    Route::delete('cards/{card}/assignees/{user}', [CardAssigneeController::class, 'unassign']);

    Route::patch('checklist-items/{item}/toggle', [ChecklistItemController::class, 'toggle']);
    Route::patch('cards/{card}/checklist-items/reorder', [ChecklistItemController::class, 'reorder']);
    Route::apiResource('cards.checklist-items', ChecklistItemController::class)
        ->shallow()
        ->except(['show'])
        ->parameters(['checklist-items' => 'item']);

    Route::apiResource('cards.comments', CommentController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);

    Route::post('invitations/{token}/accept', [WorkspaceInvitationController::class, 'accept']);
    Route::apiResource('workspaces.invitations', WorkspaceInvitationController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);
});
