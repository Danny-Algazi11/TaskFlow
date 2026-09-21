<?php

namespace App\Infrastructure\Broadcasting\Events\Card;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * One event for the whole target list's new order, not one event per card
 * — matches ReorderCardsUseCase's own model of the operation (see that
 * class's docblock): the target list's array is authoritative for "what's
 * in this list now, in what order", whether that's a same-list reorder or
 * cards dragged in from elsewhere on the same board. A listening client
 * just replaces its view of $listId's cards with $cardIds, in order.
 */
class CardsReordered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param int[] $cardIds
     */
    public function __construct(
        public readonly int $listId,
        public readonly array $cardIds,
        public readonly int $boardId,
    ) {
    }

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('board.' . $this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'cards.reordered';
    }

    public function broadcastWith(): array
    {
        return [
            'list_id' => $this->listId,
            'card_ids' => $this->cardIds,
        ];
    }
}
