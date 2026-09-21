<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceBoardFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sanctum's guard caches the user it resolves on its own RequestGuard
     * instance, which — unlike in a real app, where every request is a
     * fresh process — persists across multiple simulated requests within
     * one test method. A second actingAs() call is silently ignored by
     * auth:sanctum routes unless the cached guard is forgotten first.
     * This wraps that so every test below just reads as "act as X".
     */
    private function as(User $user): static
    {
        app('auth')->forgetGuards();
        $this->actingAs($user, 'web');

        return $this;
    }

    public function test_creating_a_workspace_makes_the_creator_its_admin(): void
    {
        $owner = User::factory()->create();

        $response = $this->as($owner)->postJson('/api/workspaces', [
            'name' => 'Product Team',
        ]);

        $response->assertCreated()->assertJsonPath('name', 'Product Team');

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $response->json('id'),
            'user_id' => $owner->id,
            'role' => 'admin',
        ]);
    }

    public function test_a_stranger_cannot_view_a_workspace_they_do_not_belong_to(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Private Workspace'])
            ->json('id');

        $this->as($stranger)
            ->getJson("/api/workspaces/{$workspaceId}")
            ->assertStatus(403);
    }

    public function test_a_nonexistent_workspace_returns_404_not_403(): void
    {
        $user = User::factory()->create();

        $this->as($user)
            ->getJson('/api/workspaces/999999')
            ->assertStatus(404);
    }

    public function test_only_an_admin_can_rename_a_workspace(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Original Name'])
            ->json('id');

        // Attach $member as a plain member (not admin) directly, bypassing
        // the invite feature since that's a later phase.
        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $member->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->as($member)
            ->putJson("/api/workspaces/{$workspaceId}", ['name' => 'Hijacked Name'])
            ->assertStatus(403);

        $this->as($owner)
            ->putJson("/api/workspaces/{$workspaceId}", ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('name', 'Renamed');
    }

    public function test_only_the_owner_can_delete_a_workspace(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Doomed Workspace'])
            ->json('id');

        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $admin->id,
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Even a fellow admin can't delete someone else's workspace.
        $this->as($admin)
            ->deleteJson("/api/workspaces/{$workspaceId}")
            ->assertStatus(403);

        $this->as($owner)
            ->deleteJson("/api/workspaces/{$workspaceId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('workspaces', ['id' => $workspaceId]);
    }

    public function test_board_crud_within_a_workspace_a_member_belongs_to(): void
    {
        $owner = User::factory()->create();

        $this->as($owner);

        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Engineering'])->json('id');

        $created = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Sprint 1']);
        $created->assertCreated()->assertJsonPath('name', 'Sprint 1');
        $boardId = $created->json('id');

        $this->getJson("/api/workspaces/{$workspaceId}/boards")
            ->assertOk()
            ->assertJsonCount(1);

        $this->getJson("/api/boards/{$boardId}")
            ->assertOk()
            ->assertJsonPath('name', 'Sprint 1');

        $this->putJson("/api/boards/{$boardId}", ['name' => 'Sprint 1 (renamed)'])
            ->assertOk()
            ->assertJsonPath('name', 'Sprint 1 (renamed)');

        $this->deleteJson("/api/boards/{$boardId}")->assertNoContent();

        $this->assertDatabaseMissing('boards', ['id' => $boardId]);
    }

    public function test_a_non_member_cannot_see_or_create_boards_in_a_workspace(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Members Only'])
            ->json('id');

        $this->as($stranger);

        $this->getJson("/api/workspaces/{$workspaceId}/boards")->assertStatus(403);
        $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Intruder Board'])
            ->assertStatus(403);
    }

    public function test_a_non_member_cannot_view_a_board_by_guessing_its_id(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $this->as($owner);
        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Secret Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Secret Board'])->json('id');

        $this->as($stranger)
            ->getJson("/api/boards/{$boardId}")
            ->assertStatus(403);
    }

    public function test_a_member_can_list_workspace_members_with_their_roles(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $member->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A plain member (not just the owner) can see the roster too.
        $members = $this->as($member)
            ->getJson("/api/workspaces/{$workspaceId}/members")
            ->assertOk()
            ->assertJsonCount(2)
            ->json();

        $byId = collect($members)->keyBy('id');
        $this->assertSame('admin', $byId[$owner->id]['role']);
        $this->assertSame('member', $byId[$member->id]['role']);
    }

    public function test_a_non_member_cannot_list_workspace_members(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        $this->as($stranger)
            ->getJson("/api/workspaces/{$workspaceId}/members")
            ->assertStatus(403);
    }
}
