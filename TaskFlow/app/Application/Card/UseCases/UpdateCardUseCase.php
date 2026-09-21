<?php

namespace App\Application\Card\UseCases;

use App\Application\Card\DTOs\UpdateCardData;
use App\Application\Card\Services\EnsureCardIsAccessible;
use App\Domain\Card\Exceptions\CardNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Broadcasting\Events\Card\CardUpdated;
use App\Infrastructure\Persistence\Models\Card;

class UpdateCardUseCase
{
    public function __construct(
        private readonly EnsureCardIsAccessible $ensureAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws CardNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(UpdateCardData $data): Card
    {
        $card = ($this->ensureAccessible)($data->cardId, $data->actingUserId);

        $updated = $this->cards->update($card, [
            'title' => $data->title,
            'description' => $data->description,
            'due_date' => $data->dueDate,
        ]);

        event(new CardUpdated($updated, (int) $updated->list->board_id));

        return $updated;
    }
}
