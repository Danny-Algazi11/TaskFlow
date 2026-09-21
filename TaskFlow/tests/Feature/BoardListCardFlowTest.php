<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardListCardFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Same actingAs()-caching workaround as WorkspaceBoardFlowTest — see
     * that file's docblock on the equivalent helper for why it's needed.
     */
    private function as(User $user): static
    {
        app('auth')->forgetGuards();
        $this->actingAs($user, 'web');

        return $this;
    }

    /**
     * @return array{0: int, 1: int} [workspaceId, boardId]
     */
    private function createWorkspaceAndBoard(User $owner): array
    {
        $this->as($owner);
        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Board'])->json('id');

        return [$workspaceId, $boardId];
    }

    public function test_list_and_card_crud_within_a_board_a_member_belongs_to(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);

        $list = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'To Do']);
        $list->assertCreated()->assertJsonPath('name', 'To Do')->assertJsonPath('position', 0);
        $listId = $list->json('id');

        $this->getJson("/api/boards/{$boardId}/lists")->assertOk()->assertJsonCount(1);

        $this->putJson("/api/lists/{$listId}", ['name' => 'To Do (renamed)'])
            ->assertOk()
            ->assertJsonPath('name', 'To Do (renamed)');

        $card = $this->postJson("/api/lists/{$listId}/cards", [
            'title' => 'Write the tests',
            'description' => 'Cover the happy path',
            'due_date' => '2026-12-01',
        ]);
        $card->assertCreated()->assertJsonPath('title', 'Write the tests')->assertJsonPath('position', 0);
        $cardId = $card->json('id');

        $this->getJson("/api/lists/{$listId}/cards")->assertOk()->assertJsonCount(1);

        $this->putJson("/api/cards/{$cardId}", [
            'title' => 'Write the tests (done)',
            'description' => null,
            'due_date' => null,
        ])->assertOk()->assertJsonPath('title', 'Write the tests (done)');

        $this->deleteJson("/api/cards/{$cardId}")->assertNoContent();
        $this->assertDatabaseMissing('cards', ['id' => $cardId]);
    }

    public function test_deleting_a_list_cascades_to_its_cards(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);

        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'Doomed List'])->json('id');
        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'Orphan-to-be'])->json('id');

        $this->deleteJson("/api/lists/{$listId}")->assertNoContent();

        $this->assertDatabaseMissing('board_lists', ['id' => $listId]);
        $this->assertDatabaseMissing('cards', ['id' => $cardId]);
    }

    public function test_a_non_member_cannot_see_lists_or_cards(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);
        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'Private List'])->json('id');
        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'Private Card'])->json('id');

        $this->as($stranger);

        $this->getJson("/api/boards/{$boardId}/lists")->assertStatus(403);
        $this->getJson("/api/lists/{$listId}")->assertStatus(403);
        $this->getJson("/api/lists/{$listId}/cards")->assertStatus(403);
        $this->getJson("/api/cards/{$cardId}")->assertStatus(403);
    }

    public function test_reordering_lists_requires_exactly_the_boards_current_list_ids(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);

        $listA = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'A'])->json('id');
        $listB = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'B'])->json('id');
        $listC = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'C'])->json('id');

        // Valid: same set, new order.
        $this->patchJson("/api/boards/{$boardId}/lists/reorder", [
            'list_ids' => [$listC, $listA, $listB],
        ])->assertNoContent();

        $ordered = $this->getJson("/api/boards/{$boardId}/lists")->json();
        $this->assertSame([$listC, $listA, $listB], array_column($ordered, 'id'));

        // Invalid: missing an id.
        $this->patchJson("/api/boards/{$boardId}/lists/reorder", [
            'list_ids' => [$listA, $listB],
        ])->assertStatus(422);

        // Invalid: a real list id, but one that belongs to a different board.
        [, $otherBoardId] = $this->createWorkspaceAndBoard($owner);
        $foreignListId = $this->postJson("/api/boards/{$otherBoardId}/lists", ['name' => 'Foreign'])->json('id');

        $this->patchJson("/api/boards/{$boardId}/lists/reorder", [
            'list_ids' => [$listA, $listB, $listC, $foreignListId],
        ])->assertStatus(422);
    }

    public function test_reordering_cards_within_the_same_list(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);
        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'List'])->json('id');

        $cardA = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'A'])->json('id');
        $cardB = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'B'])->json('id');

        $this->patchJson("/api/lists/{$listId}/cards/reorder", [
            'card_ids' => [$cardB, $cardA],
        ])->assertNoContent();

        $ordered = $this->getJson("/api/lists/{$listId}/cards")->json();
        $this->assertSame([$cardB, $cardA], array_column($ordered, 'id'));
    }

    public function test_dragging_a_card_into_a_different_list_on_the_same_board(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);
        $listA = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'A'])->json('id');
        $listB = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'B'])->json('id');

        $cardId = $this->postJson("/api/lists/{$listA}/cards", ['title' => 'Movable'])->json('id');
        $this->postJson("/api/lists/{$listB}/cards", ['title' => 'Already there']);

        // Drop the card from list A into list B, at the front.
        $existingInB = $this->getJson("/api/lists/{$listB}/cards")->json('0.id');
        $this->patchJson("/api/lists/{$listB}/cards/reorder", [
            'card_ids' => [$cardId, $existingInB],
        ])->assertNoContent();

        $this->getJson("/api/lists/{$listA}/cards")->assertOk()->assertJsonCount(0);
        $inB = $this->getJson("/api/lists/{$listB}/cards")->json();
        $this->assertSame([$cardId, $existingInB], array_column($inB, 'id'));
        $this->assertSame($listB, $inB[0]['board_list_id']);
    }

    public function test_cannot_drag_a_card_from_a_different_board_into_a_list(): void
    {
        $owner = User::factory()->create();
        [, $boardId] = $this->createWorkspaceAndBoard($owner);
        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'List'])->json('id');

        [, $otherBoardId] = $this->createWorkspaceAndBoard($owner);
        $otherListId = $this->postJson("/api/boards/{$otherBoardId}/lists", ['name' => 'Other List'])->json('id');
        $foreignCardId = $this->postJson("/api/lists/{$otherListId}/cards", ['title' => 'Foreign'])->json('id');

        $this->patchJson("/api/lists/{$listId}/cards/reorder", [
            'card_ids' => [$foreignCardId],
        ])->assertStatus(422);
    }
}
