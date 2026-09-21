<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardDetailFlowTest extends TestCase
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

    /**
     * @return array{0: int, 1: int, 2: int} [workspaceId, boardId, cardId]
     */
    private function createWorkspaceBoardListAndCard(User $owner): array
    {
        $this->as($owner);
        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Board'])->json('id');
        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'List'])->json('id');
        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'Card'])->json('id');

        return [$workspaceId, $boardId, $cardId];
    }

    // --- Checklist items ---------------------------------------------

    public function test_checklist_item_crud_toggle_and_reorder(): void
    {
        $owner = User::factory()->create();
        [, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $itemA = $this->postJson("/api/cards/{$cardId}/checklist-items", ['title' => 'A']);
        $itemA->assertCreated()->assertJsonPath('is_complete', false)->assertJsonPath('position', 0);
        $itemAId = $itemA->json('id');

        $itemBId = $this->postJson("/api/cards/{$cardId}/checklist-items", ['title' => 'B'])->json('id');

        $this->getJson("/api/cards/{$cardId}/checklist-items")->assertOk()->assertJsonCount(2);

        $this->putJson("/api/checklist-items/{$itemAId}", ['title' => 'A (renamed)'])
            ->assertOk()
            ->assertJsonPath('title', 'A (renamed)');

        $this->patchJson("/api/checklist-items/{$itemAId}/toggle")
            ->assertOk()
            ->assertJsonPath('is_complete', true);
        $this->patchJson("/api/checklist-items/{$itemAId}/toggle")
            ->assertOk()
            ->assertJsonPath('is_complete', false);

        $this->patchJson("/api/cards/{$cardId}/checklist-items/reorder", [
            'item_ids' => [$itemBId, $itemAId],
        ])->assertNoContent();

        $ordered = $this->getJson("/api/cards/{$cardId}/checklist-items")->json();
        $this->assertSame([$itemBId, $itemAId], array_column($ordered, 'id'));

        $this->deleteJson("/api/checklist-items/{$itemAId}")->assertNoContent();
        $this->assertDatabaseMissing('checklist_items', ['id' => $itemAId]);
    }

    // --- Comments -------------------------------------------------------

    public function test_comment_create_list_and_author_only_delete(): void
    {
        $owner = User::factory()->create();
        [$workspaceId, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $otherMember = User::factory()->create();
        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $otherMember->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $comment = $this->postJson("/api/cards/{$cardId}/comments", ['body' => 'Looks good']);
        $comment->assertCreated()->assertJsonPath('body', 'Looks good');
        $commentId = $comment->json('id');

        $this->getJson("/api/cards/{$cardId}/comments")->assertOk()->assertJsonCount(1);

        // A fellow workspace member can see the comment but not delete it.
        $this->as($otherMember);
        $this->getJson("/api/cards/{$cardId}/comments")->assertOk()->assertJsonCount(1);
        $this->deleteJson("/api/comments/{$commentId}")->assertStatus(403);

        // Only the author can delete their own comment.
        $this->as($owner);
        $this->deleteJson("/api/comments/{$commentId}")->assertNoContent();
        $this->assertDatabaseMissing('comments', ['id' => $commentId]);
    }

    // --- Labels -----------------------------------------------------------

    public function test_label_crud_and_attaching_to_a_card(): void
    {
        $owner = User::factory()->create();
        [$workspaceId, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $label = $this->postJson("/api/workspaces/{$workspaceId}/labels", [
            'name' => 'Bug',
            'color' => '#FF0000',
        ]);
        $label->assertCreated()->assertJsonPath('name', 'Bug');
        $labelId = $label->json('id');

        $this->getJson("/api/workspaces/{$workspaceId}/labels")->assertOk()->assertJsonCount(1);

        $this->putJson("/api/labels/{$labelId}", ['name' => 'Critical Bug', 'color' => '#CC0000'])
            ->assertOk()
            ->assertJsonPath('name', 'Critical Bug');

        $this->postJson("/api/cards/{$cardId}/labels/{$labelId}")->assertNoContent();
        $this->getJson("/api/cards/{$cardId}/labels")->assertOk()->assertJsonCount(1);

        $this->deleteJson("/api/cards/{$cardId}/labels/{$labelId}")->assertNoContent();
        $this->getJson("/api/cards/{$cardId}/labels")->assertOk()->assertJsonCount(0);

        $this->deleteJson("/api/labels/{$labelId}")->assertNoContent();
        $this->assertDatabaseMissing('labels', ['id' => $labelId]);
    }

    public function test_cannot_attach_a_label_from_a_different_workspace(): void
    {
        $owner = User::factory()->create();
        [, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        [$otherWorkspaceId] = $this->createWorkspaceBoardListAndCard($owner);
        $foreignLabelId = $this->postJson("/api/workspaces/{$otherWorkspaceId}/labels", [
            'name' => 'Foreign',
            'color' => '#00FF00',
        ])->json('id');

        $this->postJson("/api/cards/{$cardId}/labels/{$foreignLabelId}")->assertStatus(422);
    }

    // --- Assignees ----------------------------------------------------

    public function test_assigning_and_unassigning_a_workspace_member(): void
    {
        $owner = User::factory()->create();
        [$workspaceId, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $member = User::factory()->create();
        \DB::table('workspace_user')->insert([
            'workspace_id' => $workspaceId,
            'user_id' => $member->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson("/api/cards/{$cardId}/assignees/{$member->id}")->assertNoContent();

        $assignees = $this->getJson("/api/cards/{$cardId}/assignees")->json();
        $this->assertSame([$member->id], array_column($assignees, 'id'));

        $this->deleteJson("/api/cards/{$cardId}/assignees/{$member->id}")->assertNoContent();
        $this->getJson("/api/cards/{$cardId}/assignees")->assertOk()->assertJsonCount(0);
    }

    public function test_cannot_assign_a_user_who_is_not_a_workspace_member(): void
    {
        $owner = User::factory()->create();
        [, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $outsider = User::factory()->create();

        $this->postJson("/api/cards/{$cardId}/assignees/{$outsider->id}")->assertStatus(422);
    }

    // --- Cross-cutting: card detail sub-resources require card access --

    public function test_a_non_member_cannot_reach_any_card_detail_sub_resource(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [, , $cardId] = $this->createWorkspaceBoardListAndCard($owner);

        $this->as($stranger);

        $this->getJson("/api/cards/{$cardId}/checklist-items")->assertStatus(403);
        $this->getJson("/api/cards/{$cardId}/comments")->assertStatus(403);
        $this->getJson("/api/cards/{$cardId}/labels")->assertStatus(403);
        $this->getJson("/api/cards/{$cardId}/assignees")->assertStatus(403);
    }
}
