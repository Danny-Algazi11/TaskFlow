<?php

namespace App\Application\Card\UseCases;

use App\Application\BoardList\Services\EnsureListIsAccessible;
use App\Application\Card\DTOs\ReorderCardsData;
use App\Domain\BoardList\Exceptions\BoardListNotFoundException;
use App\Domain\Card\Exceptions\InvalidCardReorderException;
use App\Domain\Card\Repositories\CardRepositoryInterface;
use App\Domain\Workspace\Exceptions\WorkspaceAccessDeniedException;
use App\Infrastructure\Broadcasting\Events\Card\CardsReordered;

/**
 * Unlike a board's lists (a closed set that only gets reordered), a
 * list's cards are an OPEN set: a drag can bring in a card that used to
 * live in a different list on the same board. So this doesn't check
 * "same set as before" — it checks that every given card actually exists
 * and currently belongs to a list on the SAME BOARD as the target list.
 * That's the real security boundary: it stops a reorder call on one
 * board from reaching into another board's (and so possibly another
 * workspace's) cards, while still allowing the drag-between-lists this
 * endpoint exists for.
 */
class ReorderCardsUseCase
{
    public function __construct(
        private readonly EnsureListIsAccessible $ensureListIsAccessible,
        private readonly CardRepositoryInterface $cards,
    ) {
    }

    /**
     * @throws BoardListNotFoundException
     * @throws WorkspaceAccessDeniedException
     * @throws InvalidCardReorderException
     */
    public function execute(ReorderCardsData $data): void
    {
        $targetList = ($this->ensureListIsAccessible)($data->listId, $data->actingUserId);

        $cards = $this->cards->findMany($data->orderedCardIds);

        if ($cards->count() !== count($data->orderedCardIds)) {
            throw new InvalidCardReorderException('One or more cards do not exist.');
        }

        foreach ($cards as $card) {
            if ((int) $card->list->board_id !== (int) $targetList->board_id) {
                throw new InvalidCardReorderException();
            }
        }

        $this->cards->reorderIntoList($data->listId, $data->orderedCardIds);

        event(new CardsReordered($data->listId, $data->orderedCardIds, (int) $targetList->board_id));
    }
}
