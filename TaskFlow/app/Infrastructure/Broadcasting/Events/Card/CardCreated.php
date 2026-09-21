<?php

namespace App\Infrastructure\Broadcasting\Events\Card;

use App\Infrastructure\Persistence\Models\Card;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * ShouldBroadcastNow (synchronous), not ShouldBroadcast (queued): a queued
 * broadcast silently goes nowhere without a `queue:work` process running,
 * which is more operational overhead than a solo dev-stage project needs.
 * Worth revisiting if this ever needs to survive a broadcaster outage
 * without blocking the request.
 */
class CardCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Card $card,
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
        return 'card.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->card->id,
            'board_list_id' => $this->card->board_list_id,
            'title' => $this->card->title,
            'description' => $this->card->description,
            'position' => $this->card->position,
            'due_date' => $this->card->due_date,
        ];
    }
}
