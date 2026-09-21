<?php

namespace App\Application\Card\UseCases;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Application\Card\DTOs\CreateCardData;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Broadcasting\Events\Card\CardCreated;
use App\Infrastructure\Persistence\Models\Card;

class CreateCardUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureListIsAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     */
    public function execute(CreateCardData $data): Card
    {
        $list = ($this->ensureListIsAccessible)($data->listId, $data->actingUserId);

        $card = $this->cards->create([
            'board_list_id' => $data->listId,
            'title' => $data->title,
            'description' => $data->description,
            'due_date' => $data->dueDate,
            'position' => $this->cards->nextPosition($data->listId),
        ]);

        event(new CardCreated($card, (int) $list->board_id));

        return $card;
    }
}
