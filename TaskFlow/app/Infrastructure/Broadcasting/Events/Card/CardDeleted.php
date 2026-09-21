<?php

namespace App\Infrastructure\Broadcasting\Events\Card;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Carries plain ids rather than the Card model — by the time this fires
 * the row is already gone, so there's nothing left to serialize from.
 */
class CardDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly int $cardId,
        public readonly int $boardListId,
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
        return 'card.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->cardId,
            'board_list_id' => $this->boardListId,
        ];
    }
}
