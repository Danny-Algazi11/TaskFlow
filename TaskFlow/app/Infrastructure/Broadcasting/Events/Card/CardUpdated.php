<?php

namespace App\Infrastructure\Broadcasting\Events\Card;

use App\Infrastructure\Persistence\Models\Card;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class CardUpdated implements ShouldBroadcastNow
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
        return 'card.updated';
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
