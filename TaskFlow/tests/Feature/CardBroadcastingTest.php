<?php

namespace Tests\Feature;

use App\Infrastructure\Broadcasting\Events\Card\CardCreated;
use App\Infrastructure\Broadcasting\Events\Card\CardDeleted;
use App\Infrastructure\Broadcasting\Events\Card\CardsReordered;
use App\Infrastructure\Broadcasting\Events\Card\CardUpdated;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CardBroadcastingTest extends TestCase
{
    use RefreshDatabase;

    private function as(User $user): static
    {
        app('auth')->forgetGuards();
        $this->actingAs($user, 'web');

        return $this;
    }

    /**
     * @return array{0: int, 1: int} [boardId, listId]
     */
    private function createWorkspaceBoardAndList(User $owner): array
    {
        $this->as($owner);
        $workspaceId = $this->postJson('/api/workspaces', ['name' => 'Workspace'])->json('id');
        $boardId = $this->postJson("/api/workspaces/{$workspaceId}/boards", ['name' => 'Board'])->json('id');
        $listId = $this->postJson("/api/boards/{$boardId}/lists", ['name' => 'List'])->json('id');

        return [$boardId, $listId];
    }

    private function assertBroadcastsOnBoard(object $event, int $boardId): void
    {
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        // PrivateChannel's constructor prepends "private-" to the name it's
        // given, so the underlying board.{id} channel we construct with
        // reads back as "private-board.{id}" here.
        $this->assertSame('private-board.' . $boardId, $channels[0]->name);
    }

    public function test_creating_a_card_broadcasts_card_created(): void
    {
        Event::fake([CardCreated::class]);

        $owner = User::factory()->create();
        [$boardId, $listId] = $this->createWorkspaceBoardAndList($owner);

        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'New Card'])->json('id');

        Event::assertDispatched(CardCreated::class, function (CardCreated $event) use ($cardId, $boardId) {
            $this->assertBroadcastsOnBoard($event, $boardId);

            return $event->card->id === $cardId
                && $event->boardId === $boardId
                && $event->broadcastWith()['title'] === 'New Card';
        });
    }

    public function test_updating_a_card_broadcasts_card_updated(): void
    {
        $owner = User::factory()->create();
        [$boardId, $listId] = $this->createWorkspaceBoardAndList($owner);
        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'Original'])->json('id');

        Event::fake([CardUpdated::class]);

        $this->putJson("/api/cards/{$cardId}", ['title' => 'Renamed', 'description' => null, 'due_date' => null]);

        Event::assertDispatched(CardUpdated::class, function (CardUpdated $event) use ($cardId, $boardId) {
            $this->assertBroadcastsOnBoard($event, $boardId);

            return $event->card->id === $cardId && $event->broadcastWith()['title'] === 'Renamed';
        });
    }

    public function test_deleting_a_card_broadcasts_card_deleted_with_plain_ids(): void
    {
        $owner = User::factory()->create();
        [$boardId, $listId] = $this->createWorkspaceBoardAndList($owner);
        $cardId = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'Doomed'])->json('id');

        Event::fake([CardDeleted::class]);

        $this->deleteJson("/api/cards/{$cardId}");

        Event::assertDispatched(CardDeleted::class, function (CardDeleted $event) use ($cardId, $listId, $boardId) {
            $this->assertBroadcastsOnBoard($event, $boardId);

            return $event->cardId === $cardId && $event->boardListId === $listId;
        });
    }

    public function test_reordering_cards_broadcasts_one_cards_reordered_event(): void
    {
        $owner = User::factory()->create();
        [$boardId, $listId] = $this->createWorkspaceBoardAndList($owner);
        $cardA = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'A'])->json('id');
        $cardB = $this->postJson("/api/lists/{$listId}/cards", ['title' => 'B'])->json('id');

        Event::fake([CardsReordered::class]);

        $this->patchJson("/api/lists/{$listId}/cards/reorder", ['card_ids' => [$cardB, $cardA]]);

        Event::assertDispatched(CardsReordered::class, function (CardsReordered $event) use ($listId, $cardA, $cardB, $boardId) {
            $this->assertBroadcastsOnBoard($event, $boardId);

            return $event->listId === $listId && $event->cardIds === [$cardB, $cardA];
        });
        Event::assertDispatchedTimes(CardsReordered::class, 1);
    }

    public function test_a_failed_card_creation_does_not_broadcast(): void
    {
        Event::fake([CardCreated::class]);

        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        [, $listId] = $this->createWorkspaceBoardAndList($owner);

        // A non-member's create attempt is rejected (403) before any card
        // — and so any event — exists.
        $this->as($stranger)->postJson("/api/lists/{$listId}/cards", ['title' => 'Intruder'])
            ->assertStatus(403);

        Event::assertNotDispatched(CardCreated::class);
    }
}
