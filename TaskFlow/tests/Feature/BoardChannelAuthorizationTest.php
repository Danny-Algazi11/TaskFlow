<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

/**
 * The broadcasting/auth HTTP endpoint can't be exercised end-to-end here:
 * every built-in broadcaster except Pusher/Ably/Redis (including "null",
 * what tests run under) makes auth() a no-op that never even calls the
 * registered channel callback — there's nothing for a real Pusher/Soketi
 * connection to intercept in a test run. So this invokes the callback
 * registered in routes/channels.php directly, the same way the real
 * broadcaster would, via the public Broadcaster::getChannels() registry.
 */
class BoardChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function boardChannelCallback(): \Closure
    {
        return Broadcast::getChannels()->get('board.{boardId}');
    }

    public function test_a_workspace_member_can_authorize_on_the_board_channel(): void
    {
        $owner = User::factory()->create();
        app('auth')->forgetGuards();
        $this->actingAs($owner, 'web');

        $boardId = $this->postJson('/api/workspaces', ['name' => 'Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$boardId}/boards", ['name' => 'Board'])->json('id');

        $callback = $this->boardChannelCallback();

        $this->assertTrue(app()->call($callback, ['user' => $owner, 'boardId' => $boardId]));
    }

    public function test_a_non_member_cannot_authorize_on_the_board_channel(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        app('auth')->forgetGuards();
        $this->actingAs($owner, 'web');

        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Board'])->json('id');

        $callback = $this->boardChannelCallback();

        $this->assertFalse(app()->call($callback, ['user' => $stranger, 'boardId' => $boardId]));
    }

    public function test_authorizing_on_a_nonexistent_board_returns_false_not_an_exception(): void
    {
        $user = User::factory()->create();

        $callback = $this->boardChannelCallback();

        $this->assertFalse(app()->call($callback, ['user' => $user, 'boardId' => 999999]));
    }
}
