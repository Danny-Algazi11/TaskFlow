<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Same actingAs()-caching workaround as the other flow tests — see
     * WorkspaceBoardFlowTest's docblock on the equivalent helper.
     */
    private function as(User $user): static
    {
        app('auth')->forgetGuards();
        $this->actingAs($user, 'web');

        return $this;
    }

    public function test_admin_invites_and_the_invitee_accepts(): void
    {
        $owner = User::factory()->create();
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        $invite = $this->postJson("/api/workspaces/{$workspaceId}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ]);
        $invite->assertCreated()->assertJsonPath('email', 'invitee@example.com');
        $this->assertNotEmpty($invite->json('token'), 'the create response should expose the one-time token');
        $token = $invite->json('token');

        $this->getJson("/api/workspaces/{$workspaceId}/invitations")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonMissingPath('0.token');

        $accept = $this->as($invitee)->postJson("/api/invitations/{$token}/accept");
        $accept->assertOk()->assertJsonPath('id', $workspaceId);

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspaceId,
            'user_id' => $invitee->id,
            'role' => 'member',
        ]);

        // The invitation is no longer pending, so accepting again fails.
        $this->postJson("/api/invitations/{$token}/accept")->assertStatus(404);
    }

    public function test_only_an_admin_can_invite(): void
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

        $this->as($member)
            ->postJson("/api/workspaces/{$workspaceId}/invitations", [
                'email' => 'someone@example.com',
                'role' => 'member',
            ])
            ->assertStatus(403);
    }

    public function test_cannot_invite_someone_already_a_member(): void
    {
        $owner = User::factory()->create();
        $existingMember = User::factory()->create(['email' => 'existing@example.com']);

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $existingMember->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->as($owner)
            ->postJson("/api/workspaces/{$workspaceId}/invitations", [
                'email' => 'existing@example.com',
                'role' => 'member',
            ])
            ->assertStatus(422);
    }

    public function test_cannot_send_a_second_pending_invitation_to_the_same_email(): void
    {
        $owner = User::factory()->create();
        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        $this->postJson("/api/workspaces/{$workspaceId}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])->assertCreated();

        $this->postJson("/api/workspaces/{$workspaceId}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'admin',
        ])->assertStatus(422);
    }

    public function test_only_the_invited_email_can_accept(): void
    {
        $owner = User::factory()->create();
        $wrongPerson = User::factory()->create(['email' => 'wrong@example.com']);

        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        $token = $this->postJson("/api/workspaces/{$workspaceId}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ])->json('token');

        $this->as($wrongPerson)
            ->postJson("/api/invitations/{$token}/accept")
            ->assertStatus(403);
    }

    public function test_admin_can_revoke_a_pending_invitation(): void
    {
        $owner = User::factory()->create();
        $workspaceId = $this->as($owner)
            ->postJson('/api/workspaces', ['name' => 'Team'])
            ->json('id');

        $invite = $this->postJson("/api/workspaces/{$workspaceId}/invitations", [
            'email' => 'invitee@example.com',
            'role' => 'member',
        ]);
        $token = $invite->json('token');
        $invitationId = $invite->json('id');

        $this->deleteJson("/api/invitations/{$invitationId}")->assertNoContent();

        $this->getJson("/api/workspaces/{$workspaceId}/invitations")->assertOk()->assertJsonCount(0);

        // The token is dead once revoked.
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $this->as($invitee)
            ->postJson("/api/invitations/{$token}/accept")
            ->assertStatus(404);
    }

    public function test_a_non_admin_cannot_revoke_an_invitation(): void
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

        $invitationId = $this->as($owner)
            ->postJson("/api/workspaces/{$workspaceId}/invitations", [
                'email' => 'invitee@example.com',
                'role' => 'member',
            ])->json('id');

        $this->as($member)
            ->deleteJson("/api/invitations/{$invitationId}")
            ->assertStatus(403);
    }
}
