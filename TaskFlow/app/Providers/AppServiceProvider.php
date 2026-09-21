<?php

namespace App\Providers;

use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Domain\Board\Repositories\BoardRepositoryInterface;
use App\Domain\BoardList\Repositories\BoardListRepositoryInterface;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\ChecklistItem\Repositories\ChecklistItemRepositoryInterface;
use App\Domain\Comment\Repositories\CommentRepositoryInterface;
use App\Domain\Label\Repositories\LabelRepositoryInterface;
use App\Domain\Workspace\Repositories\WorkspaceRepositoryInterface;
use App\Domain\WorkspaceInvitation\Repositories\WorkspaceInvitationRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentBoardListRepository;
use App\Infrastructure\Persistence\Repositories\EloquentBoardRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCardRepository;
use App\Infrastructure\Persistence\Repositories\EloquentChecklistItemRepository;
use App\Infrastructure\Persistence\Repositories\EloquentCommentRepository;
use App\Infrastructure\Persistence\Repositories\EloquentLabelRepository;
use App\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use App\Infrastructure\Persistence\Repositories\EloquentWorkspaceInvitationRepository;
use App\Infrastructure\Persistence\Repositories\EloquentWorkspaceRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Every Domain repository interface gets bound to its Eloquent
        // implementation here, in one place — the container hands out the
        // right concrete class wherever a Use Case type-hints the interface.
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(WorkspaceRepositoryInterface::class, EloquentWorkspaceRepository::class);
        $this->app->bind(BoardRepositoryInterface::class, EloquentBoardRepository::class);
        $this->app->bind(BoardListRepositoryInterface::class, EloquentBoardListRepository::class);
        $this->app->bind(CardRepositoryInterface::class, EloquentCardRepository::class);
        $this->app->bind(ChecklistItemRepositoryInterface::class, EloquentChecklistItemRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
        $this->app->bind(LabelRepositoryInterface::class, EloquentLabelRepository::class);
        $this->app->bind(WorkspaceInvitationRepositoryInterface::class, EloquentWorkspaceInvitationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
