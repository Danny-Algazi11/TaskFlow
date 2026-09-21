<?php

use App\Application\Board\Services\EnsureBoardIsAccessible;
use App\Domain\Shared\DomainException;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Who's allowed to listen on a board's live-update channel is exactly the
 * same rule as who's allowed to fetch that board over the API — so this
 * reuses EnsureBoardIsAccessible rather than re-deriving "is this user a
 * workspace member" a fourth time.
 *
 * Resolved via app() inside the body, not as a third typed closure
 * parameter: a real Pusher-protocol broadcaster (confirmed against Reverb)
 * invokes this closure positionally with only ($user, ...routeParams) —
 * it does NOT use container-based injection for extra parameters the way
 * app()->call() does, which is what BoardChannelAuthorizationTest.php uses
 * to exercise this closure directly (see that file's docblock for why it
 * has to). A third typed parameter passed every one of those tests while
 * actually 500ing on every real request, since nothing in the test suite
 * goes through the real broadcaster's own invocation path.
 */
Broadcast::channel('board.{boardId}', function (User $user, int $boardId) {
    try {
        app(EnsureBoardIsAccessible::class)($boardId, $user->id);

        return true;
    } catch (DomainException) {
        return false;
    }
});
